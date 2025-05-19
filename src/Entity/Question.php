<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?int $externalCharacterId = null;

    #[ORM\Column(length: 255)]
    private ?string $correctAnswer = null;

    #[ORM\Column(length: 255)]
    private ?string $promptData = null;

    /**
     * @var Collection<int, Quiz>
     */
    #[ORM\ManyToMany(targetEntity: Quiz::class, mappedBy: 'questions')]
    private Collection $quizzes;

    public function __construct()
    {
        $this->quizzes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getExternalCharacterId(): ?int
    {
        return $this->externalCharacterId;
    }

    public function setExternalCharacterId(int $externalCharacterId): static
    {
        $this->externalCharacterId = $externalCharacterId;

        return $this;
    }

    public function getCorrectAnswer(): ?string
    {
        return $this->correctAnswer;
    }

    public function setCorrectAnswer(string $correctAnswer): static
    {
        $this->correctAnswer = $correctAnswer;

        return $this;
    }

    public function getPromptData(): ?string
    {
        return $this->promptData;
    }

    public function setPromptData(string $promptData): static
    {
        $this->promptData = $promptData;

        return $this;
    }

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function addQuiz(Quiz $quiz): static
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes->add($quiz);
            $quiz->addQuestion($this);
        }

        return $this;
    }

    public function removeQuiz(Quiz $quiz): static
    {
        if ($this->quizzes->removeElement($quiz)) {
            $quiz->removeQuestion($this);
        }

        return $this;
    }
}
