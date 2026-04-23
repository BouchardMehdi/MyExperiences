<?php

namespace App\Api;

use App\Entity\Review;

class ReviewApiPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Review $review): array
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
}
