<?php

namespace App\Api;

use App\Entity\Experience;
use App\Entity\Review;
use App\Entity\Slot;

class ExperienceApiPresenter
{
    /**
     * @param list<Experience> $experiences
     * @return list<array<string, mixed>>
     */
    public function presentList(array $experiences): array
    {
        return array_map(fn (Experience $experience): array => $this->presentListItem($experience), $experiences);
    }

    /**
     * @param list<Slot> $bookableSlots
     * @param list<Review> $latestReviews
     * @return array<string, mixed>
     */
    public function presentDetail(Experience $experience, array $bookableSlots, array $latestReviews = []): array
    {
        return [
            'id' => $experience->getId(),
            'title' => $experience->getTitle(),
            'description' => $experience->getDescription(),
            'location' => $experience->getLocation(),
            'price' => $this->presentPrice($experience->getPrice()),
            'durationMinutes' => $experience->getDuration(),
            'status' => $experience->getStatus()->value,
            'createdAt' => $experience->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'booking' => [
                'isBookable' => [] !== $bookableSlots,
                'availableSlotsCount' => count($bookableSlots),
                'nextStartAt' => $this->formatDateTime($bookableSlots[0]->getStartAt() ?? null),
            ],
            'slots' => array_map(fn (Slot $slot): array => $this->presentSlot($slot), $bookableSlots),
            'reviews' => array_map(fn (Review $review): array => $this->presentReview($review), $latestReviews),
            'reviewSummary' => [
                'averageRating' => $experience->getAverageRating(),
                'count' => $experience->getReviews()->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentListItem(Experience $experience): array
    {
        $bookableSlots = array_values(array_filter(
            $experience->getSlots()->toArray(),
            static fn (mixed $slot): bool => $slot instanceof Slot && $slot->isBookable()
        ));

        /** @var list<Slot> $bookableSlots */
        return [
            'id' => $experience->getId(),
            'title' => $experience->getTitle(),
            'summary' => $this->buildSummary($experience->getDescription()),
            'location' => $experience->getLocation(),
            'price' => $this->presentPrice($experience->getPrice()),
            'durationMinutes' => $experience->getDuration(),
            'status' => $experience->getStatus()->value,
            'createdAt' => $experience->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'booking' => [
                'isBookable' => [] !== $bookableSlots,
                'availableSlotsCount' => count($bookableSlots),
                'nextStartAt' => $this->formatDateTime($bookableSlots[0]->getStartAt() ?? null),
            ],
            'reviewSummary' => [
                'averageRating' => $experience->getAverageRating(),
                'count' => $experience->getReviews()->count(),
            ],
        ];
    }

    /**
     * @return array{amount: string, currency: string}
     */
    private function presentPrice(?string $amount): array
    {
        return [
            'amount' => $amount ?? '0.00',
            'currency' => 'EUR',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentSlot(Slot $slot): array
    {
        return [
            'id' => $slot->getId(),
            'startAt' => $this->formatDateTime($slot->getStartAt()),
            'endAt' => $this->formatDateTime($slot->getEndAt()),
            'capacity' => $slot->getCapacity(),
            'remainingPlaces' => $slot->getRemainingPlaces(),
            'isActive' => $slot->isActive(),
            'isBookable' => $slot->isBookable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentReview(Review $review): array
    {
        return [
            'id' => $review->getId(),
            'rating' => $review->getRating(),
            'comment' => $review->getComment(),
            'createdAt' => $review->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'author' => [
                'firstName' => $review->getUser()?->getFirstname(),
                'lastName' => $review->getUser()?->getLastname(),
                'fullName' => $review->getUser()?->getFullName(),
            ],
        ];
    }

    private function buildSummary(?string $description): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $description ?? '') ?? '');

        if ('' === $normalized) {
            return '';
        }

        if (mb_strlen($normalized) <= 160) {
            return $normalized;
        }

        return rtrim(mb_substr($normalized, 0, 157)).'...';
    }

    private function formatDateTime(?\DateTimeImmutable $dateTime): ?string
    {
        return $dateTime?->format(\DateTimeInterface::ATOM);
    }
}
