<?php

namespace App\Repository;

use App\Entity\DailyCharacter;
use App\Entity\Character;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTimeImmutable;

class DailyCharacterRepository extends ServiceEntityRepository
{
    private CharacterRepository $characterRepository;

    public function __construct(ManagerRegistry $registry, CharacterRepository $characterRepository)
    {
        parent::__construct($registry, DailyCharacter::class);
        $this->characterRepository = $characterRepository;
    }

    public function getOrCreateTodayCharacter(): DailyCharacter
    {
        $today = new DateTimeImmutable('today');

        $existing = $this->findOneBy(['date' => $today]);
        if ($existing) {
            return $existing;
        }

        // Tirage au sort d’un personnage
        $randomCharacter = $this->characterRepository->getRandomCharacter();

        // Création et sauvegarde du DailyCharacter
        $daily = new DailyCharacter();
        $daily->setDate($today);
        $daily->setCharacter($randomCharacter);

        $this->_em->persist($daily);
        $this->_em->flush();

        return $daily;
    }
}
