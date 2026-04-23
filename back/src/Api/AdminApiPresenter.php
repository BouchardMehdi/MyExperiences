<?php

namespace App\Api;

use App\Entity\Experience;
use App\Entity\Review;
use App\Entity\User;

class AdminApiPresenter
{
    /**
     * @param list<User> $users
     * @return list<array<string, mixed>>
     */
    public function presentUsers(array $users): array
    {
        return array_map(fn (User $user): array => $this->presentUser($user), $users);
    }

    /**
     * @param list<Experience> $experiences
     * @return list<array<string, mixed>>
     */
    public function presentExperiences(array $experiences): array
    {
        return array_map(fn (Experience $experience): array => $this->presentExperience($experience), $experiences);
    }

    /**
     * @param list<Review> $reviews
     * @return list<array<string, mixed>>
     */
    public function presentReviews(array $reviews): array
    {
        return array_map(fn (Review $review): array => $this->presentReview($review), $reviews);
    }

    /**
     * @param list<User> $users
     * @param list<Experience> $experiences
     * @param list<Review> $reviews
     * @return array<string, mixed>
     */
    public function presentDashboard(array $users, array $experiences, array $reviews): array
    {
        $publishedCount = count(array_filter($experiences, static fn (Experience $experience): bool => 'PUBLISHED' === $experience->getStatus()->value));
        $organizerCount = count(array_filter($users, static fn (User $user): bool => $user->isOrganizer()));

        return [
            'stats' => [
                'userCount' => count($users),
                'organizerCount' => $organizerCount,
                'experienceCount' => count($experiences),
                'publishedExperienceCount' => $publishedCount,
                'reviewCount' => count($reviews),
            ],
            'users' => $this->presentUsers($users),
            'experiences' => $this->presentExperiences($experiences),
            'reviews' => $this->presentReviews($reviews),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstname(),
            'lastName' => $user->getLastname(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'experienceCount' => $user->getExperiences()->count(),
            'bookingCount' => $user->getBookings()->count(),
            'reviewCount' => $user->getReviews()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentExperience(Experience $experience): array
    {
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
            'organizer' => [
                'id' => $experience->getOrganizer()?->getId(),
                'fullName' => $experience->getOrganizer()?->getFullName(),
                'email' => $experience->getOrganizer()?->getEmail(),
            ],
            'slotCount' => $experience->getSlots()->count(),
            'reviewSummary' => [
                'averageRating' => $experience->getAverageRating(),
                'count' => $experience->getReviews()->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentReview(Review $review): array
    {
        return [
            'id' => $review->getId(),
            'rating' => $review->getRating(),
            'comment' => $review->getComment(),
            'createdAt' => $review->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'author' => [
                'id' => $review->getUser()?->getId(),
                'fullName' => $review->getUser()?->getFullName(),
                'email' => $review->getUser()?->getEmail(),
            ],
            'experience' => [
                'id' => $review->getExperience()?->getId(),
                'title' => $review->getExperience()?->getTitle(),
                'status' => $review->getExperience()?->getStatus()->value,
            ],
        ];
    }
}
