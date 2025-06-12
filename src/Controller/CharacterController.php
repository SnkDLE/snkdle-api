<?php

namespace App\Controller;

use App\Entity\Character;
use App\Repository\CharacterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/character', name: 'api_character_')]
final class CharacterController extends AbstractController
{
    // Reordered routes to ensure specific routes match before wildcard routes
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, CharacterRepository $characterRepository, SerializerInterface $serializer): JsonResponse
    {
        // Définir une limite de temps d'exécution plus élevée
        set_time_limit(60);
        
        // Utiliser simplement findAll sans filtres pour éviter le timeout
        $characters = $characterRepository->findAll();
        $total = count($characters);
        
        return $this->json([
            'total' => $total,
            'data' => $characters,
        ], 200, [], [
            'groups' => ['character:read']
        ]);
    }
    
    #[Route('/daily', name: 'daily', methods: ['GET'])]
    public function dailyCharacter(CharacterRepository $characterRepository): JsonResponse
    {
        try {
            $dailyCharacter = $characterRepository->getDailyCharacter();
            
            // Si aucun personnage n'a été trouvé, on en récupère un aléatoirement
            if (!$dailyCharacter) {
                $dailyCharacter = $characterRepository->getRandomCharacter();
            }
            
            return $this->json([
                'status' => 'success',
                'data' => $dailyCharacter,
            ], 200, [], [
                'groups' => ['character:read']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to get daily character: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, CharacterRepository $characterRepository): JsonResponse
    {
        try {
            // Récupérer le paramètre de recherche
            $name = $request->query->get('name');
            
            if (!$name) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Search parameter "name" is required',
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Nettoyer le nom (supprimer les guillemets)
            $name = trim(str_replace(['"', "'"], '', $name));
            
            set_time_limit(30); // Augmenter le timeout pour les recherches
            
            // Rechercher les personnages via l'API externe
            $characters = $characterRepository->searchCharacters($name);
            
            return $this->json([
                'status' => 'success',
                'message' => count($characters) . ' character(s) found',
                'data' => $characters,
            ], 200, [], [
                'groups' => ['character:read']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to search characters: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    #[Route('/random-api', name: 'random_api', methods: ['GET'])]
    public function randomFromApi(CharacterRepository $characterRepository): JsonResponse
    {
        try {
            // Force la récupération d'un personnage depuis l'API
            $character = $characterRepository->getRandomCharacter();
            
            return $this->json([
                'status' => 'success',
                'message' => 'Character retrieved from API',
                'data' => $character,
            ], 200, [], [
                'groups' => ['character:read']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to get random character from API: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        // Augmenter la limite de temps d'exécution pour cette méthode
        set_time_limit(120);
        
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid JSON data',
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $character = new Character();
        
        // Populate character entity from request data
        if (isset($data['name'])) $character->setName($data['name']);
        if (isset($data['image'])) $character->setImage($data['image']);
        if (isset($data['species'])) $character->setSpecies($data['species']);
        if (isset($data['gender'])) $character->setGender($data['gender']);
        if (isset($data['age'])) $character->setAge($data['age']);
        if (isset($data['status'])) $character->setStatus($data['status']);
        
        // Validate entity
        $errors = $validator->validate($character);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            
            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $entityManager->persist($character);
        $entityManager->flush();
        
        return $this->json([
            'status' => 'success',
            'message' => 'Character created successfully',
            'data' => $character,
        ], Response::HTTP_CREATED, [], [
            'groups' => ['character:read']
        ]);
    }

        // These wildcard routes are placed at the end of the class to ensure specific routes match first
    
    #[Route('/{id}', name: 'show', methods: ['GET'], priority: -10)]
    public function show(Character $character): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'data' => $character,
        ], 200, [], [
            'groups' => ['character:read']
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], priority: -10)]
    public function update(Request $request, Character $character, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        // Augmenter la limite de temps d'exécution
        set_time_limit(60);
        
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid JSON data',
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Update character entity from request data
        if (isset($data['name'])) $character->setName($data['name']);
        if (isset($data['image'])) $character->setImage($data['image']);
        if (isset($data['species'])) $character->setSpecies($data['species']);
        if (isset($data['gender'])) $character->setGender($data['gender']);
        if (isset($data['age'])) $character->setAge($data['age']);
        if (isset($data['status'])) $character->setStatus($data['status']);
        
        // Validate entity
        $errors = $validator->validate($character);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            
            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $entityManager->flush();
        
        return $this->json([
            'status' => 'success',
            'message' => 'Character updated successfully',
            'data' => $character,
        ], 200, [], [
            'groups' => ['character:read']
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], priority: -10)]
    public function delete(Character $character, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($character);
        $entityManager->flush();
        
        return $this->json([
            'status' => 'success',
            'message' => 'Character deleted successfully',
        ]);
    }
}
