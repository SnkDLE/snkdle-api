<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôleur pour gérer l'authentification des utilisateurs
 */
#[Route('/api/auth', name: 'api_auth_')]
final class AuthController extends AbstractController 
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private ValidatorInterface $validator
    ) {}

    /**
     * Inscription d'un nouvel utilisateur
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        if (!isset($data['username'], $data['email'], $data['password'])) {
            return $this->json([
                'error' => 'Username, email et password sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        $userRepository = $this->entityManager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy([
            'email' => $data['email']
        ]);

        if ($existingUser) {
            return $this->json([
                'error' => 'Un utilisateur avec cet email existe déjà'
            ], Response::HTTP_CONFLICT);
        }

        $existingUsername = $userRepository->findOneBy([
            'username' => $data['username']
        ]);

        if ($existingUsername) {
            return $this->json([
                'error' => 'Ce nom d\'utilisateur est déjà pris'
            ], Response::HTTP_CONFLICT);
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        
        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Assigner le rôle par défaut
        $user->setRoles(['ROLE_USER']);

        // Générer un token API unique
        $apiToken = bin2hex(random_bytes(32));
        $user->setApiToken($apiToken);

        // Valider l'entité
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Erreurs de validation',
                'details' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        // Sauvegarder en base
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Générer le token JWT
        $token = $this->jwtManager->create($user);

        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s')
            ],
            'token' => $token,
            'apiToken' => $apiToken
        ], Response::HTTP_CREATED);
    }

    /**
     * Connexion d'un utilisateur
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        if (!isset($data['login'], $data['password'])) {
            return $this->json([
                'error' => 'Login (username ou email) et password sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Chercher l'utilisateur par username ou email
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['username' => $data['login']]) 
             ?? $userRepository->findOneBy(['email' => $data['login']]);

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non trouvé'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier le mot de passe
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'error' => 'Mot de passe incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Mettre à jour la dernière connexion
        $user->setLastLogin(new DateTimeImmutable());
        
        // Générer un nouveau token API si nécessaire
        if (!$user->getApiToken()) {
            $apiToken = bin2hex(random_bytes(32));
            $user->setApiToken($apiToken);
        }

        $this->entityManager->flush();

        // Générer le token JWT
        $token = $this->jwtManager->create($user);

        return $this->json([
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'dailyScore' => $user->getDailyScore(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'lastLogin' => $user->getLastLogin()->format('Y-m-d H:i:s')
            ],
            'token' => $token,
            'apiToken' => $user->getApiToken()
        ]);
    }
    
    /**
     * Récupérer les informations de l'utilisateur connecté
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted("ROLE_USER")]
    public function me(): JsonResponse
    {
        return $this->json($this->getUser(), context: ['groups' => ['user:read']]);
    }

    /**
     * Déconnexion (optionnel - invalide le token API)
     */
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[IsGranted("ROLE_USER")]
    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Invalider le token API
        $user->setApiToken(null);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Déconnexion réussie'
        ]);
    }
}
