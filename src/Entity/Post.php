<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[Vich\Uploadable]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Title should not be blank.")]
    #[Assert\Length(min: 5, max: 255, minMessage: "Title must be at least 5 characters long.", maxMessage: "Title cannot be longer than 255 characters.")]
    private ?string $title = null;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "Content should not be blank.")]
    #[Assert\Length(min: 10, max: 255, minMessage: "Content must be at least 10 characters long.", maxMessage: "Content cannot be longer than 255 characters.")]
    private ?string $content = null;

    #[ORM\Column(type: "datetime")]
    #[Assert\Type("\DateTimeInterface")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "author_id", referencedColumnName: "user_id", nullable: false)]
    #[Assert\NotNull(message: "Author must be provided.")]
    private ?User $author = null;

    


    #[ORM\OneToMany(mappedBy: "post", targetEntity: Comment::class, cascade: ["persist", "remove"])]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: "post", targetEntity: Like::class, cascade: ["persist", "remove"])]
    private Collection $likes;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Reaction::class, cascade: ['persist', 'remove'])]
    private Collection $reactions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->reactions = new ArrayCollection();
    }

    // Getters and setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
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

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setPost($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection|Like[]
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Like $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes[] = $like;
            $like->setPost($this);
        }
        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getPost() === $this) {
                $like->setPost(null);
            }
        }
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        
        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * @return Collection|Reaction[]
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }
}
