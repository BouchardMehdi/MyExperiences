<?php

namespace App\Api;

use App\Entity\OrganizerRequest;

class OrganizerRequestApiPresenter
{
    /**
     * @param list<OrganizerRequest> $requests
     * @return list<array<string, mixed>>
     */
    public function presentList(array $requests): array
    {
        return array_map(fn (OrganizerRequest $request): array => $this->present($request), $requests);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(OrganizerRequest $request): array
    {
        return [
            'id' => $request->getId(),
            'status' => $request->getStatus()->value,
            'motivation' => $request->getMotivation(),
            'createdAt' => $request->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'processedAt' => $request->getProcessedAt()?->format(\DateTimeInterface::ATOM),
            'user' => [
                'id' => $request->getUser()?->getId(),
                'fullName' => $request->getUser()?->getFullName(),
                'email' => $request->getUser()?->getEmail(),
            ],
            'reviewedBy' => null === $request->getReviewedBy() ? null : [
                'id' => $request->getReviewedBy()?->getId(),
                'fullName' => $request->getReviewedBy()?->getFullName(),
                'email' => $request->getReviewedBy()?->getEmail(),
            ],
        ];
    }
}
