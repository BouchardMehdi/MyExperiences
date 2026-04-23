<?php

namespace App\Security;

use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $apiToken = $this->apiTokenRepository->findActiveTokenByHash(hash('sha256', $accessToken));

        if (!$apiToken || !$apiToken->getUser()) {
            throw new BadCredentialsException('Invalid API token.');
        }

        $apiToken->touch();
        $this->entityManager->flush();

        $user = $apiToken->getUser();

        return new UserBadge(
            $user->getUserIdentifier(),
            static fn () => $user
        );
    }
}
