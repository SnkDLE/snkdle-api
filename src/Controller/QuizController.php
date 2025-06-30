<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/quiz')]
class QuizController extends AbstractController
{
    #[Route('', name: 'quiz_list', methods: ['GET'])]
    public function list(QuizRepository $quizRepo): JsonResponse
    {
        $quizzes = $quizRepo->findAll();
        return $this->json($quizzes, 200, [], ['groups' => ['quiz:read', 'question:read']]);
    }

    #[Route('/{id}', name: 'quiz_show', methods: ['GET'])]
    public function show(Quiz $quiz): JsonResponse
    {
        return $this->json($quiz, 200, [], ['groups' => ['quiz:read', 'question:read']]);
    }

    #[Route('', name: 'quiz_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $quiz = new Quiz();
        $quiz->setTitle($data['title'] ?? 'Untitled Quiz');
        $quiz->setDescription($data['description'] ?? null);
        $quiz->setDate(new \DateTime());

        $em->persist($quiz);
        $em->flush();

        return $this->json($quiz, 201, [], ['groups' => ['quiz:read']]);
    }

    #[Route('/{quizId}/add-question/{questionId}', name: 'quiz_add_question', methods: ['POST'])]
    public function addQuestion(int $quizId, int $questionId, QuizRepository $quizRepo, QuestionRepository $questionRepo, EntityManagerInterface $em): JsonResponse
    {
        $quiz = $quizRepo->find($quizId);
        $question = $questionRepo->find($questionId);

        if (!$quiz || !$question) {
            return $this->json(['error' => 'Quiz or Question not found'], 404);
        }

        $quiz->addQuestion($question);
        $em->flush();

        return $this->json(['message' => 'Question added to quiz'], 200);
    }

    #[Route('/{quizId}/remove-question/{questionId}', name: 'quiz_remove_question', methods: ['POST'])]
    public function removeQuestion(int $quizId, int $questionId, QuizRepository $quizRepo, QuestionRepository $questionRepo, EntityManagerInterface $em): JsonResponse
    {
        $quiz = $quizRepo->find($quizId);
        $question = $questionRepo->find($questionId);

        if (!$quiz || !$question) {
            return $this->json(['error' => 'Quiz or Question not found'], 404);
        }

        $quiz->removeQuestion($question);
        $em->flush();

        return $this->json(['message' => 'Question removed from quiz'], 200);
    }
}
