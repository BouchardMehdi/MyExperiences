<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Payment;
use App\Enum\BookingStatus;
use App\Enum\PaymentStatus;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class PaymentService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function simulateSuccess(Booking $booking): Payment
    {
        return $this->entityManager->wrapInTransaction(function () use ($booking): Payment {
            /** @var Booking|null $managedBooking */
            $managedBooking = $this->entityManager->find(Booking::class, $booking->getId(), LockMode::PESSIMISTIC_WRITE);

            if (!$managedBooking || !$managedBooking->canBePaid()) {
                throw new \DomainException('Cette réservation ne peut pas être payée.');
            }

            $managedBooking->setStatus(BookingStatus::PAID);

            $payment = (new Payment())
                ->setBooking($managedBooking)
                ->setAmount($managedBooking->getTotalPrice())
                ->setProvider('mock')
                ->setStatus(PaymentStatus::SUCCESS)
                ->setTransactionRef('mock_'.Uuid::v7()->toRfc4122());

            $this->entityManager->persist($managedBooking);
            $this->entityManager->persist($payment);

            return $payment;
        });
    }

    public function simulateFailure(Booking $booking): Payment
    {
        return $this->entityManager->wrapInTransaction(function () use ($booking): Payment {
            /** @var Booking|null $managedBooking */
            $managedBooking = $this->entityManager->find(Booking::class, $booking->getId(), LockMode::PESSIMISTIC_WRITE);

            if (!$managedBooking || !$managedBooking->canBePaid()) {
                throw new \DomainException('Cette réservation ne peut plus être traitée.');
            }

            $slot = $managedBooking->getSlot();
            if (!$slot) {
                throw new \DomainException('Créneau introuvable.');
            }

            $this->entityManager->lock($slot, LockMode::PESSIMISTIC_WRITE);

            $managedBooking->setStatus(BookingStatus::CANCELLED);
            $slot->setRemainingPlaces(min($slot->getCapacity(), $slot->getRemainingPlaces() + $managedBooking->getSeats()));
            $this->entityManager->persist($slot);

            $payment = (new Payment())
                ->setBooking($managedBooking)
                ->setAmount($managedBooking->getTotalPrice())
                ->setProvider('mock')
                ->setStatus(PaymentStatus::FAILED)
                ->setTransactionRef('mock_'.Uuid::v7()->toRfc4122());

            $this->entityManager->persist($managedBooking);
            $this->entityManager->persist($payment);

            return $payment;
        });
    }
}
