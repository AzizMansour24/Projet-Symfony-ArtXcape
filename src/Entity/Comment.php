<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "Comment content should not be blank.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Comment must be at least 3 characters long.", maxMessage: "Comment cannot be longer than 255 .")]
    private ?string $content = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $gifUrl = null;

    #[ORM\Column(type: "datetime")]
    #[Assert\Type("\DateTimeInterface")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $updatedAt = null;


    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "author_id", referencedColumnName: "user_id", nullable: false)]

    private ?User $author = null;


    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: "comments")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getGifUrl(): ?string
    {
        return $this->gifUrl;
    }

    public function setGifUrl(?string $gifUrl): static
    {
        $this->gifUrl = $gifUrl;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;
        return $this;
    }
}
