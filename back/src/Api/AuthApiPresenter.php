<?php

namespace App\Api;

use App\Entity\ApiToken;
use App\Entity\User;

class AuthApiPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function presentAuth(User $user, string $plainTextToken, ApiToken $apiToken, ?array $organizerRequest = null): array
    {
        return [
            'token' => [
                'type' => 'Bearer',
                'value' => $plainTextToken,
                'expiresAt' => $apiToken->getExpiresAt()->format(\DateTimeInterface::ATOM),
            ],
            'user' => $this->presentUser($user, $organizerRequest),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentUser(User $user, ?array $organizerRequest = null): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstname(),
            'lastName' => $user->getLastname(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'organizerRequest' => $organizerRequest,
        ];
    }

    /**
     * @param array<string, array<int, string>> $fieldErrors
     * @return array<string, mixed>
     */
    public function presentValidationError(array $fieldErrors): array
    {
        return [
            'error' => [
                'code' => 'validation_failed',
                'message' => 'The submitted data is invalid.',
                'fields' => $fieldErrors,
            ],
        ];
    }
}
