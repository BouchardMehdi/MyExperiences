<?php

namespace App\Security;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class BookingVoter extends Voter
{
    public const VIEW = 'BOOKING_VIEW';
    public const CANCEL = 'BOOKING_CANCEL';
    public const PAY = 'BOOKING_PAY';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Booking && in_array($attribute, [self::VIEW, self::CANCEL, self::PAY], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return $subject->getUser() === $user;
    }
}
