<?php

namespace App\Api;

use App\Entity\OrganizerRequest;
use App\Enum\OrganizerBusinessType;
use App\Enum\OrganizerEventType;

class OrganizerRequestApiPresenter
{
    public function __construct(
        private readonly OrganizerRequestScreeningPresenter $organizerRequestScreeningPresenter,
    ) {
    }

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
        $businessType = $request->getBusinessType();
        $businessTypeLabel = null === $businessType ? null : OrganizerBusinessType::tryFrom($businessType)?->label();

        return [
            'id' => $request->getId(),
            'status' => $request->getStatus()->value,
            'organizationName' => $request->getOrganizationName(),
            'phoneNumber' => $request->getPhoneNumber(),
            'streetAddress' => $request->getStreetAddress(),
            'postalCode' => $request->getPostalCode(),
            'city' => $request->getCity(),
            'country' => $request->getCountry(),
            'businessType' => $businessType,
            'businessTypeLabel' => $businessTypeLabel,
            'eventTypes' => $request->getEventTypes(),
            'eventTypeLabels' => array_map(
                static fn (string $eventType): string => OrganizerEventType::tryFrom($eventType)?->label() ?? $eventType,
                $request->getEventTypes()
            ),
            'activityDescription' => $request->getActivityDescription(),
            'websiteUrl' => $request->getWebsiteUrl(),
            'socialLinks' => $request->getSocialLinks(),
            'siret' => $request->getSiret(),
            'motivation' => $request->getMotivation(),
            'screening' => $this->organizerRequestScreeningPresenter->present($request),
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
