<?php

namespace App\Repository;

use App\Entity\Character;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    private $httpClient;

    public function __construct(ManagerRegistry $registry, HttpClientInterface $httpClient)
    {
        parent::__construct($registry, Character::class);
        $this->httpClient = $httpClient;
    }
    
    /**
     * Count total number of characters
     * 
     * @return int Total number of characters
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    /**
     * Get a random character from Attack on Titan API
     * If character already exists in database, return it without saving again
     * 
     * @return Character A character object from API or database
     * @throws \Exception If API request fails and fallback fails
     */
    public function getRandomCharacter(): Character
    {
        // Generate a random ID between 0 and 201
        $randomId = rand(0, 201);
        
        try {
            error_log("Attempting to fetch character with ID: {$randomId}");
            
            // Using the Attack on Titan API
            $response = $this->httpClient->request(
                'GET',
                "https://api.attackontitanapi.com/characters/{$randomId}",
                [
                    'timeout' => 15, // Increased timeout
                    'max_retries' => 2, // Retry mechanism
                ]
            );
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $characterData = $response->toArray();
                
                if (isset($characterData['name'])) {
                    // Transform the data to our entity format
                    $mappedData = [
                        'name' => $characterData['name'] ?? 'Unknown',
                        'img' => $characterData['img'] ?? $characterData['image'] ?? '',
                        'species' => $characterData['species'] ?? ['Human'],
                        'gender' => $characterData['gender'] ?? 'Unknown',
                        'age' => $characterData['age'] ?? mt_rand(12, 40),
                        'status' => $characterData['status'] ?? 'Unknown',
                    ];
                    
                    error_log('Character data retrieved from API: ' . json_encode($mappedData));
                    
                    try {
                        // The saveCharacterIfNotExists method will handle finding existing characters
                        return $this->saveCharacterIfNotExists($mappedData);
                    } catch (\Doctrine\DBAL\Exception\ConnectionException $dbce) {
                        error_log('DB Connection error in getRandomCharacter: ' . $dbce->getMessage());
                        
                        // Try to reconnect to database
                        $conn = $this->getEntityManager()->getConnection();
                        $conn->close();
                        $conn->connect();
                        
                        // If reconnect is successful, try to save/find again
                        return $this->saveCharacterIfNotExists($mappedData);
                    }
                }
            }
            
            throw new \Exception("API returned unexpected status code: {$statusCode}");
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            error_log('API Transport error: ' . $e->getMessage());
            throw new \Exception('Impossible de se connecter à l\'API Attack on Titan: ' . $e->getMessage());
        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
            error_log('API Client error: ' . $e->getMessage());
            throw new \Exception('Erreur de requête vers l\'API Attack on Titan: ' . $e->getMessage());
        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            
            // Try to fetch an existing character as last resort
            try {
                $fallback = $this->findOneBy([], ['id' => 'DESC']);
                if ($fallback) {
                    error_log('Using last character from database due to connection issues');
                    return $fallback;
                }
            } catch (\Exception $innerEx) {
                error_log('Fallback retrieval failed: ' . $innerEx->getMessage());
            }
            
            throw new \Exception('Erreur de connexion à la base de données: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log('General error in getRandomCharacter: ' . $e->getMessage());
            throw new \Exception('Erreur lors de la récupération d\'un personnage aléatoire: ' . $e->getMessage());
        }
    }
    


    /**
     * Save character data to database if it doesn't exist already
     * 
     * @param array $characterData Character data from API
     * @return Character Saved character entity
     */
    private function saveCharacterIfNotExists(array $characterData): Character
    {
        $entityManager = $this->getEntityManager();

        // Extract character data
        $name = $characterData['name'] ?? 'Unknown';
        $image = $characterData['img'] ?? '';
        $species = $characterData['species'] ?? ['Human'];
        $gender = $characterData['gender'] ?? 'Unknown';
        $age = isset($characterData['age']) ? (int) $characterData['age'] : 0;
        $status = $characterData['status'] ?? 'Unknown';
        
        error_log('Trying to save character: ' . $name);
        
        // First check by name for a quick match
        $existingCharacter = $this->findOneBy(['name' => $name]);
        
        if ($existingCharacter) {
            error_log('Character already exists by name: ' . $name);
            return $existingCharacter;
        }

        
        try {
            // Create new character if no existing one was found
            $character = new Character();
            $character->setName($name);
            $character->setImage($image);
            
            // Handle species (convert to array if needed)
            if (!is_array($species)) {
                $species = [$species];
            }
            $character->setSpecies($species);
            
            $character->setGender($gender);
            $character->setAge($age);
            $character->setStatus($status);
            
            // Save to database
            $entityManager->persist($character);
            $entityManager->flush();
            
            error_log('New character saved: ' . $name);
            return $character;
        } catch (\Exception $e) {
            // Log the exception
            error_log('Error saving character: ' . $e->getMessage());
            throw $e; // Rethrow the exception for upstream handling
        }
    }
    
    
    /**
     * Get daily character (latest character added to database)
     * 
     * @return Character|null The daily character or null if none exists
     */
    public function getDailyCharacter(): ?Character
    {
        try {
            error_log('Fetching daily character...');
            
            $conn = $this->getEntityManager()->getConnection();
            
            // Check if connection is still alive, if not reconnect
            if (!$conn->isConnected()) {
                error_log('Connection lost in getDailyCharacter, attempting to reconnect...');
                $conn->connect();
            }
            
            $dailyCharacter = $this->createQueryBuilder('c')
                ->orderBy('c.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
                
            if ($dailyCharacter) {
                error_log('Daily character found: ' . $dailyCharacter->getName());
            } else {
                error_log('No daily character found in database');
            }
            
            return $dailyCharacter;
        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            error_log('Database connection error in getDailyCharacter: ' . $e->getMessage());
            try {
                // Try to reconnect
                $conn = $this->getEntityManager()->getConnection();
                $conn->close();
                $conn->connect();
                
                // Try a simple query to verify connection
                $conn->executeQuery('SELECT 1');
                
                // If we get here, connection is restored - retry
                error_log('Connection restored, retrying getDailyCharacter...');
                
                return $this->createQueryBuilder('c')
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
            } catch (\Exception $reconnectEx) {
                error_log('Failed to reconnect in getDailyCharacter: ' . $reconnectEx->getMessage());
                return null;
            }
        } catch (\Exception $e) {
            error_log('Error getting daily character: ' . $e->getMessage());
            return null;
        }
    }
}
