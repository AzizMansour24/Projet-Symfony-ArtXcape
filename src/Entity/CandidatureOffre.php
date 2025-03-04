<?php

namespace App\Entity;

use App\Repository\CandidatureOffreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CandidatureOffreRepository::class)]
#[Vich\Uploadable]
class CandidatureOffre
{
    public const STATUS_EN_ATTENTE = 'En attente';
    public const STATUS_ACCEPTE = 'Sélectionnée';
    public const STATUS_REJETE = 'Rejetée';

    private static array $allowedStatuses = [
        self::STATUS_EN_ATTENTE,
        self::STATUS_ACCEPTE,
        self::STATUS_REJETE
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "candidat_id", referencedColumnName: "user_id", nullable: false)]
    private ?User $candidat = null;

    #[ORM\ManyToOne(targetEntity: Emploi::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Emploi $emploi = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Les compétences sont obligatoires')]
    #[Assert\Length(
        min: 20,
        minMessage: 'Veuillez détailler vos compétences (minimum {{ limit }} caractères)'
    )]
    private ?string $competences = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La lettre de motivation est obligatoire')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Votre lettre de motivation doit contenir au moins {{ limit }} caractères'
    )]
    private ?string $lettreMotivation = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $statut = self::STATUS_EN_ATTENTE;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateCandidature = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_association;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[Vich\UploadableField(mapping: 'cv_files', fileNameProperty: 'cvFilename')]
    #[Assert\File(
        maxSize: '24M',
        mimeTypes: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        mimeTypesMessage: 'Veuillez télécharger un document valide (PDF ou Word)'
    )]
    private ?File $cvFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFilename = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Candidature::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Candidature $candidature = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    public function __construct()
    {
        $this->dateCandidature = new \DateTime();
        $this->date_association = new \DateTime();
        $this->statut = self::STATUS_EN_ATTENTE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidat(): ?User
    {
        return $this->candidat;
    }

    public function setCandidat(?User $candidat): self
    {
        $this->candidat = $candidat;
        return $this;
    }

    public function getEmploi(): ?Emploi
    {
        return $this->emploi;
    }

    public function setEmploi(?Emploi $emploi): self
    {
        $this->emploi = $emploi;
        return $this;
    }

    public function getCompetences(): ?string
    {
        return $this->competences;
    }

    public function setCompetences(string $competences): self
    {
        $this->competences = $competences;
        return $this;
    }

    public function getLettreMotivation(): ?string
    {
        return $this->lettreMotivation;
    }

    public function setLettreMotivation(string $lettreMotivation): self
    {
        $this->lettreMotivation = $lettreMotivation;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        if (!in_array($statut, self::$allowedStatuses)) {
            throw new \InvalidArgumentException('Statut invalide. Les statuts autorisés sont: ' . implode(', ', self::$allowedStatuses));
        }
        $this->statut = $statut;
        return $this;
    }

    public function getDateCandidature(): ?\DateTimeInterface
    {
        return $this->dateCandidature;
    }

    public function setDateCandidature(\DateTimeInterface $dateCandidature): self
    {
        $this->dateCandidature = $dateCandidature;
        return $this;
    }

    public function getDateAssociation(): ?\DateTimeInterface
    {
        return $this->date_association;
    }

    public function setDateAssociation(\DateTimeInterface $date_association): self
    {
        $this->date_association = $date_association;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function getCvFilename(): ?string
    {
        return $this->cvFilename;
    }

    public function setCvFilename(?string $cvFilename): void
    {
        $this->cvFilename = $cvFilename;
    }

    public function getCvFile(): ?File
    {
        return $this->cvFile;
    }

    public function setCvFile(?File $cvFile = null): void
    {
        $this->cvFile = $cvFile;

        if (null !== $cvFile) {
            $this->updatedAt = new \DateTime();
        }
    }

    public function getCandidature(): ?Candidature
    {
        return $this->candidature;
    }

    public function setCandidature(?Candidature $candidature): self
    {
        $this->candidature = $candidature;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
} 