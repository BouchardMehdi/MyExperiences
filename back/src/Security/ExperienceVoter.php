<?php

namespace App\Security;

use App\Entity\Experience;
use App\Entity\User;
use App\Enum\ExperienceStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class ExperienceVoter extends Voter
{
    public const MANAGE = 'EXPERIENCE_MANAGE';
    public const VIEW = 'EXPERIENCE_VIEW';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Experience && in_array($attribute, [self::MANAGE, self::VIEW], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return self::VIEW === $attribute && ExperienceStatus::PUBLISHED === $subject->getStatus();
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return match ($attribute) {
            self::MANAGE => $this->security->isGranted('ROLE_ORGANIZER') && $subject->getOrganizer() === $user,
            self::VIEW => ExperienceStatus::PUBLISHED === $subject->getStatus() || $subject->getOrganizer() === $user,
            default => false,
        };
    }
}
