<?php

namespace App\Controller;

use App\Repository\CharacterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GuessController extends AbstractController
{
    #[Route('/api/guess', name: 'api_guess', methods: ['POST'])]
    public function guess(Request $request, CharacterRepository $characterRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => true, 'message' => 'Invalid JSON: ' . $e->getMessage()], 400);
        }


        $inputName = $data['name'] ?? null;

        if (!$inputName) {
            return $this->json(['error' => true, 'message' => 'Missing name'], 400);
        }

        try {
            $guessResults = $characterRepository->searchCharacters($inputName);
            if (empty($guessResults)) {
                return $this->json(['error' => true, 'message' => 'Character not found'], 404);
            }

            $guessedCharacter = $guessResults[0];
            $dailyCharacter = $characterRepository->getDailyCharacter();

            if (!$dailyCharacter) {
                return $this->json(['error' => true, 'message' => 'No daily character available'], 500);
            }

            return $this->json([
                'match' => $guessedCharacter->getName() === $dailyCharacter->getName(),
                'guess' => [
                    'name' => $guessedCharacter->getName(),
                    'image' => $guessedCharacter->getImage(),
                    'gender' => $guessedCharacter->getGender(),
                    'species' => $guessedCharacter->getSpecies(),
                    'age' => $guessedCharacter->getAge(),
                    'status' => $guessedCharacter->getStatus(),
                ],
                'compare' => [
                    'gender' => $guessedCharacter->getGender() === $dailyCharacter->getGender(),
                    'species' => $guessedCharacter->getSpecies() === $dailyCharacter->getSpecies(),
                    'age' => $guessedCharacter->getAge() === $dailyCharacter->getAge(),
                    'status' => $guessedCharacter->getStatus() === $dailyCharacter->getStatus(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => true,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
