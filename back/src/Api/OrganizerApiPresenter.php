<?php

namespace App\Api;

use App\Entity\Booking;
use App\Entity\Experience;
use App\Entity\Payment;
use App\Entity\Slot;

class OrganizerApiPresenter
{
    /**
     * @param list<Experience> $experiences
     * @return list<array<string, mixed>>
     */
    public function presentExperiences(array $experiences): array
    {
        return array_map(fn (Experience $experience): array => $this->presentExperience($experience), $experiences);
    }

    /**
     * @param list<Booking> $bookings
     * @return list<array<string, mixed>>
     */
    public function presentBookings(array $bookings): array
    {
        return array_map(fn (Booking $booking): array => $this->presentBooking($booking), $bookings);
    }

    /**
     * @param list<Experience> $experiences
     * @param list<Booking> $bookings
     * @param list<Slot> $slots
     * @return array<string, mixed>
     */
    public function presentDashboard(array $experiences, array $bookings, array $slots): array
    {
        return [
            'stats' => [
                'experienceCount' => count($experiences),
                'slotCount' => count($slots),
                'bookingCount' => count($bookings),
                'pendingBookingCount' => count(array_filter($bookings, static fn (Booking $booking): bool => 'PENDING' === $booking->getStatus()->value)),
                'paidBookingCount' => count(array_filter($bookings, static fn (Booking $booking): bool => 'PAID' === $booking->getStatus()->value)),
            ],
            'experiences' => $this->presentExperiences($experiences),
            'bookings' => $this->presentBookings($bookings),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentExperience(Experience $experience): array
    {
        $slots = $experience->getSlots()->toArray();
        $reviews = $experience->getReviews()->toArray();

        return [
            'id' => $experience->getId(),
            'title' => $experience->getTitle(),
            'description' => $experience->getDescription(),
            'location' => $experience->getLocation(),
            'price' => [
                'amount' => $experience->getPrice() ?? '0.00',
                'currency' => 'EUR',
            ],
            'durationMinutes' => $experience->getDuration(),
            'status' => $experience->getStatus()->value,
            'createdAt' => $experience->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'reviewSummary' => [
                'averageRating' => $experience->getAverageRating(),
                'count' => count($reviews),
            ],
            'slots' => array_map(
                fn (mixed $slot): array => $slot instanceof Slot ? $this->presentSlot($slot) : [],
                $slots
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentSlot(Slot $slot): array
    {
        return [
            'id' => $slot->getId(),
            'startAt' => $slot->getStartAt()?->format(\DateTimeInterface::ATOM),
            'endAt' => $slot->getEndAt()?->format(\DateTimeInterface::ATOM),
            'capacity' => $slot->getCapacity(),
            'remainingPlaces' => $slot->getRemainingPlaces(),
            'bookedSeats' => max(0, $slot->getCapacity() - $slot->getRemainingPlaces()),
            'isActive' => $slot->isActive(),
            'isBookable' => $slot->isBookable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentBooking(Booking $booking): array
    {
        $slot = $booking->getSlot();
        $experience = $slot?->getExperience();
        $user = $booking->getUser();
        $payments = $booking->getPayments()->toArray();
        $latestPayment = [] !== $payments && $payments[0] instanceof Payment ? $payments[0] : null;

        return [
            'id' => $booking->getId(),
            'status' => $booking->getStatus()->value,
            'seats' => $booking->getSeats(),
            'totalPrice' => [
                'amount' => $booking->getTotalPrice(),
                'currency' => 'EUR',
            ],
            'createdAt' => $booking->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'customer' => [
                'id' => $user?->getId(),
                'fullName' => $user?->getFullName(),
                'email' => $user?->getEmail(),
            ],
            'experience' => [
                'id' => $experience?->getId(),
                'title' => $experience?->getTitle(),
                'location' => $experience?->getLocation(),
            ],
            'slot' => [
                'id' => $slot?->getId(),
                'startAt' => $slot?->getStartAt()?->format(\DateTimeInterface::ATOM),
                'endAt' => $slot?->getEndAt()?->format(\DateTimeInterface::ATOM),
            ],
            'latestPayment' => null === $latestPayment ? null : [
                'id' => $latestPayment->getId(),
                'status' => $latestPayment->getStatus()->value,
                'provider' => $latestPayment->getProvider(),
                'transactionRef' => $latestPayment->getTransactionRef(),
                'createdAt' => $latestPayment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
        ];
    }
}
