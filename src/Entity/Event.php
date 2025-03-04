<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $idevent = null;

    #[ORM\Column(name: "titre", length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(name: "description", type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(name: "dateEvenement", type: 'datetime')]
    #[Assert\NotBlank(message: "La date est obligatoire")]
    #[Assert\GreaterThan(
        value: "today",
        message: "La date de l'événement doit être ultérieure à aujourd'hui"
    )]
    private ?\DateTimeInterface $dateEvenement = null;

    #[ORM\Column(name: "lieu", length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le lieu doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le lieu ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $lieu = null;

    #[ORM\Column(name: "nombreBillets")]
    #[Assert\NotBlank(message: "Le nombre de billets est obligatoire")]
    #[Assert\Positive(message: "Le nombre de billets doit être supérieur à 0")]
    #[Assert\LessThan(
        value: 10000,
        message: "Le nombre de billets ne peut pas dépasser 10000"
    )]
    private ?int $nombreBillets = null;

    #[ORM\Column(name: "image", length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: "timestart", type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: "L'heure de début est obligatoire")]
    private ?\DateTimeInterface $timestart = null;

    #[ORM\Column(name: "event_mission", type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        min: 10,
        minMessage: "La mission doit contenir au moins {{ limit }} caractères"
    )]
    private ?string $eventMission = null;

    #[ORM\Column(name: "donation_objective", type: Types::FLOAT, nullable: true)]
    #[Assert\PositiveOrZero(message: "L'objectif de don doit être positif ou nul")]
    #[Assert\LessThan(
        value: 1000000,
        message: "L'objectif de don ne peut pas dépasser 1 000 000"
    )]
    private ?float $donationObjective = null;

    #[ORM\Column(name: "seatprice", type: Types::FLOAT)]
    #[Assert\NotBlank(message: "Le prix du billet est obligatoire")]
    #[Assert\Positive(message: "Le prix du billet doit être supérieur à 0")]
    #[Assert\LessThan(
        value: 10000,
        message: "Le prix du billet ne peut pas dépasser 10000"
    )]
    private ?float $seatprice = null;

    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'event')]
    private Collection $donations;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\OneToMany(targetEntity: Model3D::class, mappedBy: 'event')]
    private Collection $models3D;

    public function __construct()
    {
        $this->donations = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->models3D = new ArrayCollection();
    }

    public function getidevent(): ?int
    {
        return $this->idevent;
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

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateEvenement(): ?\DateTimeInterface
    {
        return $this->dateEvenement;
    }

    public function setDateEvenement(?\DateTimeInterface $dateEvenement): self
    {
        $this->dateEvenement = $dateEvenement;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getNombreBillets(): ?int
    {
        return $this->nombreBillets;
    }

    public function setNombreBillets(int $nombreBillets): self
    {
        $this->nombreBillets = $nombreBillets;
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

    public function getTimestart(): ?\DateTimeInterface
    {
        return $this->timestart;
    }

    public function setTimestart(\DateTimeInterface $timestart): static
    {
        $this->timestart = $timestart;
        return $this;
    }

    public function getEventMission(): ?string
    {
        return $this->eventMission;
    }

    public function setEventMission(?string $eventMission): static
    {
        $this->eventMission = $eventMission;
        return $this;
    }

    public function getDonationObjective(): ?float
    {
        return $this->donationObjective;
    }

    public function setDonationObjective(?float $donationObjective): static
    {
        $this->donationObjective = $donationObjective;
        return $this;
    }

    public function getSeatprice(): ?float
    {
        return $this->seatprice;
    }

    public function setSeatprice(float $seatprice): static
    {
        $this->seatprice = $seatprice;
        return $this;
    }

    /**
     * @return Collection<int, Donation>
     */
    public function getDonations(): Collection
    {
        return $this->donations;
    }

    public function addDonation(Donation $donation): static
    {
        if (!$this->donations->contains($donation)) {
            $this->donations->add($donation);
            $donation->setidEvent($this);
        }
        return $this;
    }

    public function removeDonation(Donation $donation): static
    {
        if ($this->donations->removeElement($donation)) {
            if ($donation->getidEvent() === $this) {
                $donation->setidEvent(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setEvent($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getEvent() === $this) {
                $reservation->setEvent(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Model3D>
     */
    public function getModels3D(): Collection
    {
        return $this->models3D;
    }

    public function addModel3D(Model3D $model3D): self
    {
        if (!$this->models3D->contains($model3D)) {
            $this->models3D->add($model3D);
            $model3D->setEvent($this);
        }
        return $this;
    }

    public function removeModel3D(Model3D $model3D): self
    {
        if ($this->models3D->removeElement($model3D)) {
            if ($model3D->getEvent() === $this) {
                $model3D->setEvent(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        // Adjust this to return the property you want to display
        return $this->titre ?? (string)$this->idevent;
    }
} 