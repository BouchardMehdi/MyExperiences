<?php

namespace App\Entity;

use App\Repository\SlotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SlotRepository::class)]
class Slot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'slots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Experience $experience = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est requise.')]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est requise.')]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La capacité est requise.')]
    #[Assert\Positive(message: 'La capacité doit être supérieure à 0.')]
    private int $capacity = 0;

    #[ORM\Column]
    private int $remainingPlaces = 0;

    #[ORM\Column]
    private bool $isActive = true;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(mappedBy: 'slot', targetEntity: Booking::class, orphanRemoval: true)]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExperience(): ?Experience
    {
        return $this->experience;
    }

    public function setExperience(?Experience $experience): static
    {
        $this->experience = $experience;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $reservedSeats = max(0, $this->capacity - $this->remainingPlaces);
        $this->capacity = $capacity;
        $this->remainingPlaces = max(0, $capacity - $reservedSeats);

        if (0 === $reservedSeats) {
            $this->remainingPlaces = $capacity;
        }

        return $this;
    }

    public function getRemainingPlaces(): int
    {
        return $this->remainingPlaces;
    }

    public function setRemainingPlaces(int $remainingPlaces): static
    {
        $this->remainingPlaces = max(0, $remainingPlaces);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setSlot($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking) && $booking->getSlot() === $this) {
            $booking->setSlot(null);
        }

        return $this;
    }

    #[Assert\Callback]
    public function validateDates(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if ($this->startAt && $this->endAt && $this->endAt <= $this->startAt) {
            $context->buildViolation('La fin du créneau doit être après le début.')
                ->atPath('endAt')
                ->addViolation();
        }
    }

    public function isBookable(): bool
    {
        return $this->isActive && $this->remainingPlaces > 0 && $this->startAt > new \DateTimeImmutable();
    }
}
