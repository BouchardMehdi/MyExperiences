<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Slot;
use App\Entity\User;
use App\Enum\BookingStatus;
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
            throw new \DomainException('Vous devez reserver au moins une place.');
        }

        return $this->entityManager->wrapInTransaction(function () use ($user, $slot, $seats): Booking {
            /** @var Slot|null $lockedSlot */
            $lockedSlot = $this->entityManager->find(Slot::class, $slot->getId(), LockMode::PESSIMISTIC_WRITE);

            if (!$lockedSlot || !$lockedSlot->isBookable()) {
                throw new \DomainException('Ce creneau n est plus disponible.');
            }

            if (!$lockedSlot->getExperience()?->isPublished()) {
                throw new \DomainException('Cette experience n est pas ouverte a la reservation.');
            }

            if ($lockedSlot->getRemainingPlaces() < $seats) {
                throw new \DomainException('Le nombre de places demande n est plus disponible.');
            }

            if ($this->bookingRepository->hasActiveBookingForUserAndSlot($user, $lockedSlot)) {
                throw new \DomainException('Vous avez deja une reservation active sur ce creneau.');
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
                throw new \DomainException('Reservation introuvable.');
            }

            if ($managedBooking->isCancelled()) {
                throw new \DomainException('Cette reservation est deja annulee.');
            }

            $slot = $managedBooking->getSlot();
            if (!$slot) {
                throw new \DomainException('Creneau introuvable.');
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
