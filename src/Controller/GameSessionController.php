<?php
namespace App\Controller;

use App\Entity\GameSession;
use App\Repository\GameSessionRepository;
use App\Repository\CharacterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/game-session')]
class GameSessionController extends AbstractController
{
    #[Route('/start', name: 'game_session_start', methods: ['POST'])]
    public function start(Request $request, EntityManagerInterface $em, CharacterRepository $characterRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $player = $data['player'] ?? null;

        // Récupérer personnage du jour (ou le dernier)
        $characterOfTheDay = $characterRepo->getDailyCharacter();
        if (!$characterOfTheDay) {
            return $this->json(['error' => 'Personnage du jour non trouvé'], 500);
        }

        $gameSession = new GameSession();
        $gameSession->setPlayer($player);
        $gameSession->setStartedAt(new \DateTime());
        $gameSession->setWon(false);
        $gameSession->setCharacterOfTheDay($characterOfTheDay);

        $em->persist($gameSession);
        $em->flush();

        return $this->json([
            'id' => $gameSession->getId(),
            'startedAt' => $gameSession->getStartedAt()->format('c'),
            'character' => $characterOfTheDay->getName(),
        ]);
    }

    #[Route('/end/{id}', name: 'game_session_end', methods: ['POST'])]
    public function end(int $id, Request $request, EntityManagerInterface $em, GameSessionRepository $gameRepo): JsonResponse
    {
        $gameSession = $gameRepo->find($id);
        if (!$gameSession) {
            return $this->json(['error' => 'Session non trouvée'], 404);
        }

        if ($gameSession->getEndedAt() !== null) {
            return $this->json(['error' => 'Session déjà terminée'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $won = $data['won'] ?? false;

        $endedAt = new \DateTime();
        $gameSession->setEndedAt($endedAt);

        $duration = $endedAt->getTimestamp() - $gameSession->getStartedAt()->getTimestamp();
        $gameSession->setDurationInSeconds($duration);
        $gameSession->setWon((bool) $won);

        $em->flush();

        return $this->json([
            'id' => $gameSession->getId(),
            'endedAt' => $gameSession->getEndedAt()->format('c'),
            'durationInSeconds' => $duration,
            'won' => $gameSession->isWon(),
        ]);
    }

    #[Route('/leaderboard', name: 'game_session_leaderboard', methods: ['GET'])]
    public function leaderboard(GameSessionRepository $gameRepo): JsonResponse
    {
        $topSessions = $gameRepo->createQueryBuilder('g')
            ->andWhere('g.won = :won')
            ->setParameter('won', true)
            ->orderBy('g.durationInSeconds', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = array_map(fn($session) => [
            'player' => $session->getPlayer(),
            'durationInSeconds' => $session->getDurationInSeconds(),
            'character' => $session->getCharacterOfTheDay()->getName(),
            'endedAt' => $session->getEndedAt()->format('c'),
        ], $topSessions);

        return $this->json([
            'top' => $data,
        ]);
    }
    #[Route('/guess/{id}', name: 'game_session_guess', methods: ['POST'])]
public function guess(int $id, Request $request, GameSessionRepository $gameRepo, CharacterRepository $characterRepo, EntityManagerInterface $em): JsonResponse
{
    $session = $gameRepo->find($id);
    if (!$session) {
        return $this->json(['error' => 'Session non trouvée'], 404);
    }

    $data = json_decode($request->getContent(), true);
    $guessName = $data['name'] ?? null;
    if (!$guessName) {
        return $this->json(['error' => 'Nom du personnage manquant'], 400);
    }

    $guessedCharacter = $characterRepo->findOneBy(['name' => $guessName]);
    if (!$guessedCharacter) {
        return $this->json(['error' => 'Personnage non trouvé'], 404);
    }

    $dailyCharacter = $session->getCharacterOfTheDay();
    if (!$dailyCharacter) {
        return $this->json(['error' => 'Personnage du jour non défini dans la session'], 500);
    }

    $comparison = [
        'gender' => $guessedCharacter->getGender() === $dailyCharacter->getGender(),
        'species' => $guessedCharacter->getSpecies() === $dailyCharacter->getSpecies(),
        'age' => $guessedCharacter->getAge() === $dailyCharacter->getAge(),
        'status' => $guessedCharacter->getStatus() === $dailyCharacter->getStatus(),
    ];

    $isMatch = $guessedCharacter->getId() === $dailyCharacter->getId();

    if ($isMatch) {
        $session->setWon(true);
        $session->setEndedAt(new \DateTime());
        $duration = $session->getEndedAt()->getTimestamp() - $session->getStartedAt()->getTimestamp();
        $session->setDurationInSeconds($duration);
        $em->flush();
    }

    return $this->json([
        'match' => $isMatch,
        'comparison' => $comparison,
        'guessed' => $guessedCharacter->getName(),
    ]);
}


}
