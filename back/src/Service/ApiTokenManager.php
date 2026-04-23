<?php

namespace App\Service;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApiTokenManager
{
    private const TOKEN_TTL_DAYS = 30;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiTokenRepository $apiTokenRepository,
    ) {
    }

    /**
     * @return array{plainTextToken: string, apiToken: ApiToken}
     */
    public function issueToken(User $user): array
    {
        if (null !== $user->getId()) {
            $this->apiTokenRepository->revokeExpiredTokensForUser($user);
        }

        $plainTextToken = $this->generatePlainTextToken();
        $apiToken = (new ApiToken())
            ->setUser($user)
            ->setTokenHash($this->hashToken($plainTextToken))
            ->setExpiresAt(new \DateTimeImmutable(sprintf('+%d days', self::TOKEN_TTL_DAYS)));

        $this->entityManager->persist($apiToken);

        return [
            'plainTextToken' => $plainTextToken,
            'apiToken' => $apiToken,
        ];
    }

    public function revokeToken(string $plainTextToken): bool
    {
        return $this->apiTokenRepository->revokeByHash($this->hashToken($plainTextToken));
    }

    public function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    private function generatePlainTextToken(): string
    {
        return 'myexp_'.rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }
}
