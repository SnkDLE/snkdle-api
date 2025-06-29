<?php

namespace App\Repository;

use App\Entity\Character;
use App\Service\CacheManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;
    
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * Constructeur du repository.
     *
     * @param ManagerRegistry     $registry     Gestionnaire d'entités Doctrine
     * @param HttpClientInterface $httpClient   Client HTTP pour les requêtes API
     * @param CacheManager        $cacheManager Gestionnaire de cache
     */
    public function __construct(
        ManagerRegistry $registry, 
        HttpClientInterface $httpClient,
        CacheManager $cacheManager
    ) {
        parent::__construct($registry, Character::class);
        $this->httpClient = $httpClient;
        $this->cacheManager = $cacheManager;
    }
    
    /**
     * Compte le nombre total de personnages en base de données
     * 
     * @return int Nombre total de personnages
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les personnages avec cache
     * 
     * @return Character[] Liste de tous les personnages
     */
    public function findAllCached(): array
    {
        $cacheKey = $this->cacheManager->generateCharacterCacheKey('all');
        
        return $this->cacheManager->get($cacheKey, function () {
            return $this->findAll();
        }, 3600); // Cache de 1 heure
    }
    
    /**
     * Recherche des personnages par nom via l'API Attack on Titan
     * Les résultats sont mis en cache pour améliorer les performances.
     * 
     * @param string $name Le nom à rechercher
     * @return array Liste des entités Character
     * @throws \Exception Si la requête API échoue
     */
    public function searchCharacters(string $name): array
    {
        // Générer une clé de cache basée sur le nom recherché
        $cacheKey = $this->cacheManager->generateCharacterCacheKey('search', $name);
        
        // Utiliser le cache avec un TTL de 1 heure (3600 secondes)
        return $this->cacheManager->get($cacheKey, function () use ($name) {
            try {
                // Requête vers l'API Attack on Titan pour rechercher par nom
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
                    $searchResults = $response->toArray();
                    $characters = [];
                    
                    // Les APIs renvoient les résultats dans 'results' ou directement en tableau
                    $characterResults = $searchResults['results'] ?? $searchResults;
                    
                    if (is_array($characterResults)) {
                        foreach ($characterResults as $characterData) {
                            // Ignorer les résultats sans nom valide
                            if (!isset($characterData['name'])) {
                                continue;
                            }
                            
                            // Transformer les données API au format de notre entité
                            $mappedData = [
                                'name' => $characterData['name'] ?? 'Unknown',
                                'img' => $characterData['img'] ?? $characterData['image'] ?? '',
                                'species' => $characterData['species'] ?? ['Human'],
                                'gender' => $characterData['gender'] ?? 'Unknown',
                                'age' => $characterData['age'] ?? mt_rand(12, 40),
                                'status' => $characterData['status'] ?? 'Unknown',
                            ];
                            
                            try {
                                // Sauvegarder le personnage en BDD (ou récupérer s'il existe)
                                $character = $this->saveCharacterIfNotExists($mappedData);
                                $characters[] = $character;
                            } catch (\Exception $e) {
                                // Loguer l'erreur mais continuer avec les autres résultats
                                error_log("Error saving character {$mappedData['name']}: " . $e->getMessage());
                            }
                        }
                    }
                    
                    // Si aucun résultat depuis l'API, chercher en base de données locale
                    if (empty($characters)) {
                        // Recherche approximative en base de données locale
                        // Nettoyer le nom pour éviter les injections SQL
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
                
                // Fallback : recherche en base de données en cas d'échec API
                // Nettoyer le nom pour éviter les injections SQL
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
        }, 3600); // Cache valide pendant 1 heure
    }
    
    
    /**
     * Récupère un personnage aléatoire depuis l'API Attack on Titan
     * Si le personnage existe déjà en BDD, le retourne sans le sauvegarder à nouveau
     * Les résultats sont mis en cache pour améliorer les performances.
     * 
     * @return Character Un objet personnage depuis l'API ou la BDD
     * @throws \Exception Si la requête API échoue et que le fallback échoue
     */
    public function getRandomCharacter(): Character
    {
        // Générer une clé de cache pour le personnage aléatoire (par heure)
        $cacheKey = $this->cacheManager->generateCharacterCacheKey('random', date('Y-m-d-H'));
        
        // Utiliser le cache avec un TTL de 1 heure
        return $this->cacheManager->get($cacheKey, function () {
            // Générer un ID aléatoire entre 0 et 201 (limite de l'API)
            $randomId = rand(0, 201);
            
            try {
                error_log("Attempting to fetch character with ID: {$randomId}");
                
                // Appel à l'API Attack on Titan avec l'ID aléatoire
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
                    // Transformer les données API au format de notre entité
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
                        // Sauvegarder ou récupérer le personnage existant
                        return $this->saveCharacterIfNotExists($mappedData);
                    } catch (\Doctrine\DBAL\Exception\ConnectionException $dbce) {
                        error_log('DB Connection error in getRandomCharacter: ' . $dbce->getMessage());
                        
                        // Tentative de reconnexion à la base de données
                        $conn = $this->getEntityManager()->getConnection();
                        $conn->close();
                        $conn->connect();
                        
                        // Si la reconnexion réussit, réessayer de sauvegarder
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
            
            // Tentative de récupération d'un personnage existant en dernier recours
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
        }, 3600); // Cache valide pendant 1 heure
    }
    


    /**
     * Sauvegarde les données d'un personnage en BDD s'il n'existe pas déjà
     * 
     * @param array $characterData Données du personnage depuis l'API
     * @return Character Entité personnage sauvegardée ou existante
     */
    private function saveCharacterIfNotExists(array $characterData): Character
    {
        $entityManager = $this->getEntityManager();

        // Extraction des données du personnage avec valeurs par défaut
        $name = $characterData['name'] ?? 'Unknown';
        $image = $characterData['img'] ?? '';
        $species = $characterData['species'] ?? ['Human'];
        $gender = $characterData['gender'] ?? 'Unknown';
        $age = isset($characterData['age']) ? (int) $characterData['age'] : 0;
        $status = $characterData['status'] ?? 'Unknown';
        
        error_log('Trying to save character: ' . $name);
        
        // Vérifier d'abord si le personnage existe déjà (recherche par nom)
        $existingCharacter = $this->findOneBy(['name' => $name]);
        
        if ($existingCharacter) {
            error_log('Character already exists by name: ' . $name);
            return $existingCharacter;
        }
        
        try {
            // Créer un nouveau personnage si aucun existant trouvé
            $character = new Character();
            $character->setName($name);
            $character->setImage($image);
            
            // Gérer les espèces (convertir en tableau si nécessaire)
            if (!is_array($species)) {
                $species = [$species];
            }
            $character->setSpecies($species);
            
            $character->setGender($gender);
            $character->setAge($age);
            $character->setStatus($status);            
            
            // Sauvegarder en base de données
            $entityManager->persist($character);
            $entityManager->flush();
            
            // Invalider les caches liés aux personnages après ajout
            $this->cacheManager->invalidateCharacterCaches();
            
            error_log('New character saved: ' . $name);
            return $character;
        } catch (\Exception $e) {
            // Logger l'exception et la relancer
            error_log('Error saving character: ' . $e->getMessage());
            throw $e; // Relancer pour gestion en amont
        }
    }
    
    
    /**
     * Récupère le personnage du jour (dernier personnage ajouté en BDD)
     * Les résultats sont mis en cache pour améliorer les performances.
     * 
     * @return Character|null Le personnage du jour ou null si aucun n'existe
     */
    public function getDailyCharacter(): ?Character
    {
        // Générer une clé de cache basée sur la date du jour (un personnage par jour)
        $cacheKey = $this->cacheManager->generateCharacterCacheKey('daily', date('Y-m-d'));
        
        // Utiliser le cache avec un TTL d'une journée (86400 secondes)
        return $this->cacheManager->get($cacheKey, function () {
            try {
                error_log('Fetching daily character...');
                
                $conn = $this->getEntityManager()->getConnection();
            
            // Vérifier si la connexion est toujours active, sinon se reconnecter
            if (!$conn->isConnected()) {
                error_log('Connection lost in getDailyCharacter, attempting to reconnect...');
                $conn->connect();
            }
            
            // Récupérer le dernier personnage ajouté (par ID décroissant)
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
                // Tentative de reconnexion
                $conn = $this->getEntityManager()->getConnection();
                $conn->close();
                $conn->connect();
                
                // Tester la connexion avec une requête simple
                $conn->executeQuery('SELECT 1');
                
                // Si on arrive ici, la connexion est restaurée - réessayer
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
        }, 86400); // Cache valide pendant 1 journée
    }
    
    /**
     * Récupère un personnage aléatoire depuis l'API Attack on Titan sans utiliser le cache
     * Cette méthode est similaire à getRandomCharacter mais n'utilise pas le système de cache
     * 
     * @return Character Un objet personnage depuis l'API ou la BDD
     * @throws \Exception Si la requête API échoue et que le fallback échoue
     */
    public function getRandomCharacterWithoutCache(): Character
    {
        // Générer un ID aléatoire entre 0 et 201 (limite de l'API)
        $randomId = rand(0, 201);
        
        try {
            error_log("Attempting to fetch fresh character with ID: {$randomId}");
            
            // Appel direct à l'API Attack on Titan sans cache
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
                    // Transformer les données API au format de notre entité
                    $mappedData = [
                        'name' => $characterData['name'] ?? 'Unknown',
                        'img' => $characterData['img'] ?? $characterData['image'] ?? '',
                        'species' => $characterData['species'] ?? ['Human'],
                        'gender' => $characterData['gender'] ?? 'Unknown',
                        'age' => $characterData['age'] ?? mt_rand(12, 40),
                        'status' => $characterData['status'] ?? 'Unknown',
                    ];
                    
                    error_log('Fresh character data retrieved from API: ' . json_encode($mappedData));
                    
                    try {
                        // Sauvegarder ou récupérer le personnage existant
                        return $this->saveCharacterIfNotExists($mappedData);
                    } catch (\Doctrine\DBAL\Exception\ConnectionException $dbce) {
                        error_log('DB Connection error in getRandomCharacterWithoutCache: ' . $dbce->getMessage());
                        
                        // Tentative de reconnexion à la base de données
                        $conn = $this->getEntityManager()->getConnection();
                        $conn->close();
                        $conn->connect();
                        
                        // Si la reconnexion réussit, réessayer de sauvegarder
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
            
            // Tentative de récupération d'un personnage existant en dernier recours
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
            error_log('General error in getRandomCharacterWithoutCache: ' . $e->getMessage());
            throw new \Exception('Erreur lors de la récupération d\'un personnage aléatoire frais: ' . $e->getMessage());
        }
    }
}
