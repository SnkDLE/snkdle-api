<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use DateTimeImmutable;

class JWTAuthenticationSuccessListener
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    /**
     * Ajoute des données personnalisées au token JWT lors de l'authentification réussie
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        // Si c'est notre entité User, on ajoute des données supplémentaires
        if ($user instanceof User) {
            $data['user'] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'dailyScore' => $user->getDailyScore(),
            ];

            // Mettre à jour la date du dernier login
            $user->setLastLogin(new DateTimeImmutable());
            $this->entityManager->flush();
            
            $event->setData($data);
        }
    }
}
