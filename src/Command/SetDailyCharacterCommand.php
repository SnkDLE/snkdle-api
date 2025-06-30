<?php

namespace App\Command;

use App\Entity\DailyCharacter;
use App\Repository\CharacterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:set-daily-character',
    description: 'Sélectionne un personnage aléatoire chaque jour'
)]
class SetDailyCharacterCommand extends Command
{
    private EntityManagerInterface $em;
    private CharacterRepository $characterRepo;

    public function __construct(EntityManagerInterface $em, CharacterRepository $characterRepo)
    {
        parent::__construct();
        $this->em = $em;
        $this->characterRepo = $characterRepo;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = (new \DateTime())->setTime(0, 0, 0);

        // Vérifier si un personnage est déjà défini pour aujourd’hui
        $existing = $this->em->getRepository(DailyCharacter::class)->findOneBy(['date' => $today]);
        if ($existing) {
            $output->writeln('Personnage déjà défini pour aujourd’hui : ' . $existing->getCharacter()->getName());
            return Command::SUCCESS;
        }

        $random = $this->characterRepo->findOneBy([], ['id' => 'RANDOM()']); // PostgreSQL
        if (!$random) {
            $output->writeln('<error>Aucun personnage en base</error>');
            return Command::FAILURE;
        }

        $daily = new DailyCharacter();
        $daily->setCharacter($random);
        $daily->setDate($today);

        $this->em->persist($daily);
        $this->em->flush();

        $output->writeln('Personnage du jour défini : ' . $random->getName());

        return Command::SUCCESS;
    }
}
