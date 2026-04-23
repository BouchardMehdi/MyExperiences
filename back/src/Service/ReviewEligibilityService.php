<?php

namespace App\Service;

use App\Entity\Experience;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\ReviewRepository;

class ReviewEligibilityService
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly ReviewRepository $reviewRepository,
    ) {
    }

    public function canReview(User $user, Experience $experience): bool
    {
        if (!$this->bookingRepository->hasParticipatedInExperience($user, $experience)) {
            return false;
        }

        return null === $this->reviewRepository->findOneBy([
            'user' => $user,
            'experience' => $experience,
        ]);
    }
}
