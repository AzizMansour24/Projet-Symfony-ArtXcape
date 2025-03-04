<?php

namespace App\Entity;

use App\Repository\EmploiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: EmploiRepository::class)]
class Emploi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 20,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'Les compétences requises sont obligatoires')]
    private ?string $competences_requises = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\NotBlank(message: 'Le budget est obligatoire')]
    #[Assert\Positive(message: 'Le budget doit être positif')]
    private ?string $budget = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire')]
    private ?string $lieu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_publication = null;
    public function __construct()
    {
        // Set the default publication date to the current date and time
        $this->date_publication = new \DateTime();
    }

    #[ORM\Column(length: 10)]
    private string $statut = 'Ouvert';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "user_id", nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeContrat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $niveauExperience = null;

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCompetencesRequises(): ?string
    {
        return $this->competences_requises;
    }

    public function setCompetencesRequises(?string $competences_requises): static
    {
        $this->competences_requises = $competences_requises;
        return $this;
    }

    public function getBudget(): ?string
    {
        return $this->budget;
    }

    public function setBudget(?string $budget): static
    {
        $this->budget = $budget;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    public function setDatePublication(\DateTimeInterface $date_publication): static
    {
        $this->date_publication = $date_publication;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        if (!in_array($statut, ['Ouvert', 'Fermé', 'En cours'])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->statut = $statut;
        return $this;
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

    public function getTypeContrat(): ?string
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(?string $typeContrat): self
    {
        $this->typeContrat = $typeContrat;
        return $this;
    }

    public function getNiveauExperience(): ?string
    {
        return $this->niveauExperience;
    }

    public function setNiveauExperience(?string $niveauExperience): self
    {
        $this->niveauExperience = $niveauExperience;
        return $this;
    }
}