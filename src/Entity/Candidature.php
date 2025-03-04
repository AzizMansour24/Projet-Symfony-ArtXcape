<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "user_id", nullable: false)]
    #[Assert\NotNull(message: 'Vous devez être un utilisateur enregistré pour postuler.')]
    private $candidat;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'Veuillez décrire vos compétences.')]
    #[Assert\Length(
        min: 5,
        max: 1000,
        minMessage: 'Les compétences doivent contenir au moins {{ limit }} caractères.',
        maxMessage: 'Les compétences ne peuvent pas dépasser {{ limit }} caractères.'
    )]
    private $competences;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'Veuillez rédiger une lettre de motivation.')]
    #[Assert\Length(
        min: 5,
        max: 2000,
        minMessage: 'La lettre de motivation doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La lettre de motivation ne peut pas dépasser {{ limit }} caractères.'
    )]
    private $lettreMotivation;

    #[ORM\Column(type: 'datetime')]
    private $dateCandidature;

    #[ORM\OneToMany(targetEntity: CandidatureOffre::class, mappedBy: 'candidature', cascade: ['remove'])]
    private $candidatureOffres;

    public function __construct()
    {
        $this->candidatureOffres = new ArrayCollection();
        $this->dateCandidature = new \DateTime(); 
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

    public function getCompetences(): ?string
    {
        return $this->competences;
    }

    public function setCompetences(?string $competences): self
    {
        $this->competences = $competences;
        return $this;
    }

    public function getLettreMotivation(): ?string
    {
        return $this->lettreMotivation;
    }

    public function setLettreMotivation(?string $lettreMotivation): self
    {
        $this->lettreMotivation = $lettreMotivation;
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
}