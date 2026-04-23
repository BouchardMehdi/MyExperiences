<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Slot;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Enum\ExperienceStatus;
use App\Repository\BookingRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookingRepository $bookingRepository,
    ) {
    }

    public function createBooking(User $user, Slot $slot, int $seats): Booking
    {
        if ($seats < 1) {
            throw new \DomainException('Vous devez réserver au moins une place.');
        }

        return $this->entityManager->wrapInTransaction(function () use ($user, $slot, $seats): Booking {
            /** @var Slot|null $lockedSlot */
            $lockedSlot = $this->entityManager->find(Slot::class, $slot->getId(), LockMode::PESSIMISTIC_WRITE);

            if (!$lockedSlot || !$lockedSlot->isBookable()) {
                throw new \DomainException('Ce créneau n’est plus disponible.');
            }

            if (ExperienceStatus::PUBLISHED !== $lockedSlot->getExperience()?->getStatus()) {
                throw new \DomainException('Cette expérience n’est pas ouverte à la réservation.');
            }

            if ($lockedSlot->getRemainingPlaces() < $seats) {
                throw new \DomainException('Le nombre de places demandé n’est plus disponible.');
            }

            if ($this->bookingRepository->hasActiveBookingForUserAndSlot($user, $lockedSlot)) {
                throw new \DomainException('Vous avez déjà une réservation active sur ce créneau.');
            }

            $booking = (new Booking())
                ->setUser($user)
                ->setSlot($lockedSlot)
                ->setSeats($seats)
                ->setStatus(BookingStatus::PENDING)
                ->setTotalPrice(number_format((float) $lockedSlot->getExperience()?->getPrice() * $seats, 2, '.', ''));

            $lockedSlot->setRemainingPlaces($lockedSlot->getRemainingPlaces() - $seats);

            $this->entityManager->persist($booking);
            $this->entityManager->persist($lockedSlot);

            return $booking;
        });
    }

    public function cancelBooking(Booking $booking): Booking
    {
        return $this->entityManager->wrapInTransaction(function () use ($booking): Booking {
            /** @var Booking|null $managedBooking */
            $managedBooking = $this->entityManager->find(Booking::class, $booking->getId(), LockMode::PESSIMISTIC_WRITE);

            if (!$managedBooking) {
                throw new \DomainException('Réservation introuvable.');
            }

            if (BookingStatus::CANCELLED === $managedBooking->getStatus()) {
                throw new \DomainException('Cette réservation est déjà annulée.');
            }

            $slot = $managedBooking->getSlot();
            if (!$slot) {
                throw new \DomainException('Créneau introuvable.');
            }

            $this->entityManager->lock($slot, LockMode::PESSIMISTIC_WRITE);
            $slot->setRemainingPlaces(min($slot->getCapacity(), $slot->getRemainingPlaces() + $managedBooking->getSeats()));
            $managedBooking->setStatus(BookingStatus::CANCELLED);

            $this->entityManager->persist($slot);
            $this->entityManager->persist($managedBooking);

            return $managedBooking;
        });
    }
}
