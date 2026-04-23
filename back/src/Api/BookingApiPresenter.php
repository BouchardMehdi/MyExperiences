<?php

namespace App\Api;

use App\Entity\Booking;

class BookingApiPresenter
{
    /**
     * @param list<Booking> $bookings
     * @return list<array<string, mixed>>
     */
    public function presentList(array $bookings): array
    {
        return array_map(fn (Booking $booking): array => $this->present($booking), $bookings);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Booking $booking): array
    {
        $slot = $booking->getSlot();
        $experience = $slot?->getExperience();
        $payments = $booking->getPayments()->toArray();
        $latestPayment = [] !== $payments ? $payments[0] : null;

        return [
            'id' => $booking->getId(),
            'status' => $booking->getStatus()->value,
            'seats' => $booking->getSeats(),
            'totalPrice' => [
                'amount' => $booking->getTotalPrice(),
                'currency' => 'EUR',
            ],
            'createdAt' => $booking->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'canCancel' => !$booking->isCancelled(),
            'canPay' => $booking->canBePaid(),
            'experience' => [
                'id' => $experience?->getId(),
                'title' => $experience?->getTitle(),
                'location' => $experience?->getLocation(),
            ],
            'slot' => [
                'id' => $slot?->getId(),
                'startAt' => $slot?->getStartAt()?->format(\DateTimeInterface::ATOM),
                'endAt' => $slot?->getEndAt()?->format(\DateTimeInterface::ATOM),
                'remainingPlaces' => $slot?->getRemainingPlaces(),
            ],
            'latestPayment' => null === $latestPayment ? null : [
                'id' => $latestPayment->getId(),
                'status' => $latestPayment->getStatus()->value,
                'createdAt' => $latestPayment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
        ];
    }
}
