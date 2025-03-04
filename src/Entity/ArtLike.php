<?php

namespace App\Entity;

use App\Repository\ArtLikeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtLikeRepository::class)]
class ArtLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "user_id", nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'artLikes')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Article $article = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $likedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;
        return $this;
    }

    public function getLikedAt(): ?\DateTimeInterface
    {
        return $this->likedAt;
    }

    public function setLikedAt(\DateTimeInterface $likedAt): self
    {
        $this->likedAt = $likedAt;
        return $this;
    }
} 