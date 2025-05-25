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
    
    #[Route('/random', name: 'random', methods: ['GET'])]
    public function random(Request $request, CharacterRepository $characterRepository): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 5);
        $limit = min(max($limit, 1), 20); // Between 1 and 20
        
        $characters = $characterRepository->findRandom($limit);
        
        return $this->json([
            'data' => $characters,
        ], 200, [], [
            'groups' => ['character:read']
        ]);
    }
    
    #[Route('/paginated', name: 'paginated', methods: ['GET'])]
    public function paginated(Request $request, CharacterRepository $characterRepository): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(max((int) $request->query->get('limit', 20), 1), 50);
        
        $characters = $characterRepository->findPaginated($page, $limit);
        
        // Obtenez le nombre total de personnages (méthode plus efficace que count(findAll()))
        $total = $characterRepository->countTotal();
        
        return $this->json([
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
            'data' => $characters,
        ], 200, [], [
            'groups' => ['character:read']
        ]);
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

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Character $character): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'data' => $character,
        ], 200, [], [
            'groups' => ['character:read']
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
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

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
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
