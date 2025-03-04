<?php

namespace App\Entity;

use App\Repository\BadWordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BadWordRepository::class)]
class BadWord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $word = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $replacement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWord(): ?string
    {
        return $this->word;
    }

    public function setWord(string $word): self
    {
        $this->word = $word;
        return $this;
    }

    public function getReplacement(): ?string
    {
        return $this->replacement;
    }

    public function setReplacement(?string $replacement): self
    {
        $this->replacement = $replacement;
        return $this;
    }
} 