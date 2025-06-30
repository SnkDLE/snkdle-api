<?php

namespace App\Entity;

use App\Repository\GameSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameSessionRepository::class)]
class GameSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationInSeconds = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $player = null;

    #[ORM\Column]
    private ?bool $won = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Character $characterOfTheDay = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeInterface
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeInterface $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getDurationInSeconds(): ?int
    {
        return $this->durationInSeconds;
    }

    public function setDurationInSeconds(?int $durationInSeconds): static
    {
        $this->durationInSeconds = $durationInSeconds;

        return $this;
    }

    public function getPlayer(): ?string
    {
        return $this->player;
    }

    public function setPlayer(?string $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function isWon(): ?bool
    {
        return $this->won;
    }

    public function setWon(bool $won): static
    {
        $this->won = $won;

        return $this;
    }

    public function getCharacterOfTheDay(): ?Character
    {
        return $this->characterOfTheDay;
    }

    public function setCharacterOfTheDay(?Character $characterOfTheDay): static
    {
        $this->characterOfTheDay = $characterOfTheDay;

        return $this;
    }
}
