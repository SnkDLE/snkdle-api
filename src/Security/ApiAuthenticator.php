<?php 

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiAuthenticator extends AbstractAuthenticator
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function supports(Request $request): ?bool
    {
        // Vérifier si la requête a un token API valide
        $hasAuth = $request->headers->has('Authorization');
        $hasBearer = $hasAuth && str_contains($request->headers->get('Authorization'), 'Bearer ');
        
        error_log("ApiAuthenticator::supports - hasAuth: " . ($hasAuth ? 'true' : 'false') . ", hasBearer: " . ($hasBearer ? 'true' : 'false'));
        
        return $hasBearer;
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = str_replace('Bearer ', '', $request->headers->get('Authorization'));
        
        error_log("ApiAuthenticator::authenticate - Token: " . substr($apiToken, 0, 10) . "...");
        
        if (empty($apiToken)) {
            error_log("ApiAuthenticator::authenticate - Token vide");
            throw new AuthenticationException('Token API manquant');
        }
        
        // Retourner un Passport avec un UserBadge qui utilise l'apiToken pour trouver l'utilisateur
        return new SelfValidatingPassport(
            new UserBadge($apiToken, function($apiToken) {
                // Chercher l'utilisateur par son apiToken
                $user = $this->userRepository->findOneBy(['apiToken' => $apiToken]);
                
                error_log("ApiAuthenticator::authenticate - User found: " . ($user ? $user->getUsername() : 'null'));
                
                if (!$user) {
                    error_log("ApiAuthenticator::authenticate - Token invalide: " . substr($apiToken, 0, 10) . "...");
                    throw new AuthenticationException('Token API invalide');
                }
                
                return $user;
            })
        ); 
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Handle successful authentication, e.g., return a response or redirect
        return null; // No response needed for API authentication
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Handle authentication failure, e.g., return an error response
        return new JsonResponse([
            'message' => 'Authentication failed',
            'error' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}













