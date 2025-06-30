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
     * Search characters by name using the Attack on Titan API
     * 
     * @param string $name The name to search for
     * @return array List of Character entities
     * @throws \Exception If API request fails
     */
    public function searchCharacters(string $name): array
    {
        try {
            // Query the Attack on Titan API
            $response = $this->httpClient->request(
                'GET',
                'https://api.attackontitanapi.com/characters',
                [
                    'query' => [
                        'name' => $name
                    ],
                    'timeout' => 15
                ]
            );
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $content = $response->getContent(false); // brut, sans décodage
                $utf8Content = mb_convert_encoding($content, 'UTF-8', 'auto');
                $searchResults = json_decode($utf8Content, true, 512, JSON_THROW_ON_ERROR);
                $characters = [];
                
                // Les APIs renvoient généralement les résultats dans un tableau 'results' ou directement
                $characterResults = $searchResults['results'] ?? $searchResults;
                
                if (is_array($characterResults)) {
                    foreach ($characterResults as $characterData) {
                        // Ignorer les résultats sans nom
                        if (!isset($characterData['name'])) {
                            continue;
                        }
                        
                        // Transformer les données au format de notre entité
                        $mappedData = [
                            'name' => $characterData['name'] ?? 'Unknown',
                            'img' => $characterData['img'] ?? $characterData['image'] ?? '',
                            'species' => $characterData['species'] ?? ['Human'],
                            'gender' => $characterData['gender'] ?? 'Unknown',
                            'age' => $characterData['age'] ?? mt_rand(12, 40),
                            'status' => $characterData['status'] ?? 'Unknown',
                        ];
                        
                        try {
                            // Sauvegarder le personnage et l'ajouter aux résultats
                            $character = $this->saveCharacterIfNotExists($mappedData);
                            $characters[] = $character;
                        } catch (\Exception $e) {
                            // Loguer l'erreur mais continuer avec les autres résultats
                            error_log("Error saving character {$mappedData['name']}: " . $e->getMessage());
                        }
                    }
                }
                
                // Si aucun résultat depuis l'API, chercher en base de données
                if (empty($characters)) {
                    // Recherche approximative en base de données
                    // Nettoyer le nom pour éviter les problèmes avec les guillemets
                    $cleanName = trim(str_replace(['"', "'"], '', $name));
                    
                    $characters = $this->createQueryBuilder('c')
                        ->where('LOWER(c.name) LIKE LOWER(:name)')
                        ->setParameter('name', '%' . strtolower($cleanName) . '%')
                        ->getQuery()
                        ->getResult();
                }
                
                return $characters;
            }
            
            throw new \Exception("API returned unexpected status code: {$statusCode}");
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            error_log('API Transport error: ' . $e->getMessage());
            
            // Fallback: recherche en base de données en cas d'échec API
            // Nettoyer le nom pour éviter les problèmes avec les guillemets
            $cleanName = trim(str_replace(['"', "'"], '', $name));
            
            $characters = $this->createQueryBuilder('c')
                ->where('LOWER(c.name) LIKE LOWER(:name)')
                ->setParameter('name', '%' . strtolower($cleanName) . '%')
                ->getQuery()
                ->getResult();
                
            if (!empty($characters)) {
                return $characters;
            }
            
            throw new \Exception('Impossible de se connecter à l\'API Attack on Titan: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log('Error searching characters: ' . $e->getMessage());
            throw new \Exception('Erreur lors de la recherche de personnages: ' . $e->getMessage());
        }
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
                'timeout' => 15,
                // No max_retries
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
    
    
   public function getDailyCharacter(): ?Character
{
    return $this->createQueryBuilder('c')
        ->orderBy('c.id', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}

}
