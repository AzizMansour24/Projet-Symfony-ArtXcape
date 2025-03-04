<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reservation', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'userid', referencedColumnName: 'user_id', nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'idevent', referencedColumnName: 'idevent', nullable: true)]
    private ?Event $event = null;

    #[ORM\Column(name: 'reservation_date', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'seats_reserved', type: Types::INTEGER)]
    #[Assert\NotBlank(message: 'Please enter the number of seats')]
    #[Assert\Type(type: 'integer', message: 'The number of seats must be a valid number')]
    #[Assert\Positive(message: 'The number of seats must be greater than 0')]
    #[Assert\LessThanOrEqual(
        propertyPath: 'event.nombreBillets',
        message: 'You cannot reserve more seats than available ({{ compared_value }} seats available)'
    )]
    private ?int $seats = null;

    #[ORM\Column(name: 'total_amount', type: Types::FLOAT)]
    #[Assert\NotBlank(message: 'Total amount is required')]
    #[Assert\Type(type: 'float', message: 'Total amount must be a valid number')]
    #[Assert\PositiveOrZero(message: 'Total amount cannot be negative')]
    private ?float $totalAmount = null;

    public function id_reservation(): ?int
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

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getSeats(): ?int
    {
        return $this->seats;
    }

    public function setSeats(int $seats): self
    {
        $this->seats = $seats;
        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }
} 