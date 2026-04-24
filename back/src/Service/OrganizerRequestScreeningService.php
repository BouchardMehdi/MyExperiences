<?php

namespace App\Service;

use App\Entity\OrganizerRequest;
use App\Enum\OrganizerRequestScreeningStatus;

class OrganizerRequestScreeningService
{
    public function __construct(
        private readonly OrganizerAddressLookupService $addressLookupService,
        private readonly OrganizerSiretLookupService $organizerSiretLookupService,
    ) {
    }

    public function screen(OrganizerRequest $organizerRequest): void
    {
        $checks = [
            'phone' => $this->screenPhone($organizerRequest),
            'siret' => $this->screenSiret($organizerRequest),
            'address' => $this->screenAddress($organizerRequest),
            'profile' => $this->screenProfile($organizerRequest),
            'contact' => $this->screenContactCompleteness($organizerRequest),
        ];

        $organizerRequest->setScreeningChecks($checks);

        $hasFailure = $this->hasCheckStatus($checks, 'failed');
        $hasWarning = $this->hasCheckStatus($checks, 'warning');

        if ($hasFailure) {
            $organizerRequest
                ->setScreeningStatus(OrganizerRequestScreeningStatus::AUTO_REJECTED)
                ->rejectAutomatically();

            return;
        }

        $organizerRequest->setScreeningStatus(
            $hasWarning
                ? OrganizerRequestScreeningStatus::NEEDS_REVIEW
                : OrganizerRequestScreeningStatus::PRE_VALIDATED
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function screenPhone(OrganizerRequest $organizerRequest): array
    {
        $digits = preg_replace('/\D+/', '', $organizerRequest->getPhoneNumber() ?? '');
        if (!is_string($digits) || '' === $digits) {
            return [
                'status' => 'failed',
                'message' => 'Le numero de telephone est introuvable dans le dossier.',
            ];
        }

        $country = mb_strtolower(trim((string) $organizerRequest->getCountry()));

        if (in_array($country, ['france', 'fr', 'france metropolitaine'], true)) {
            if (str_starts_with($digits, '33')) {
                $digits = '0' . substr($digits, 2);
            }

            if (preg_match('/^0[1-9]\d{8}$/', $digits)) {
                return [
                    'status' => 'passed',
                    'message' => 'Numero francais coherent.',
                    'normalized' => $digits,
                ];
            }

            return [
                'status' => 'failed',
                'message' => 'Le numero de telephone ne correspond pas a un format francais exploitable.',
                'normalized' => $digits,
            ];
        }

        if (preg_match('/^\d{8,15}$/', $digits)) {
            return [
                'status' => 'passed',
                'message' => 'Numero de telephone coherent.',
                'normalized' => $digits,
            ];
        }

        return [
            'status' => 'failed',
            'message' => 'Le numero de telephone reste inexploitable pour verification.',
            'normalized' => $digits,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function screenSiret(OrganizerRequest $organizerRequest): array
    {
        $siret = preg_replace('/\D+/', '', $organizerRequest->getSiret() ?? '');
        if (!is_string($siret) || 14 !== strlen($siret)) {
            return [
                'status' => 'failed',
                'message' => 'Le SIRET doit contenir exactement 14 chiffres.',
            ];
        }

        if (!$this->passesLuhn($siret)) {
            return [
                'status' => 'failed',
                'message' => 'Le SIRET echoue au controle de coherence numerique.',
            ];
        }

        return $this->organizerSiretLookupService->verify(
            $siret,
            (string) $organizerRequest->getOrganizationName(),
            (string) $organizerRequest->getPostalCode(),
            (string) $organizerRequest->getCity()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function screenAddress(OrganizerRequest $organizerRequest): array
    {
        return $this->addressLookupService->verify(
            (string) $organizerRequest->getStreetAddress(),
            (string) $organizerRequest->getPostalCode(),
            (string) $organizerRequest->getCity(),
            (string) $organizerRequest->getCountry()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function screenProfile(OrganizerRequest $organizerRequest): array
    {
        $descriptionLength = mb_strlen((string) $organizerRequest->getActivityDescription());
        $motivationLength = mb_strlen((string) $organizerRequest->getMotivation());

        if ($descriptionLength < 80 || $motivationLength < 30) {
            return [
                'status' => 'failed',
                'message' => "La description d'activite ou la motivation est trop courte pour etre exploitable.",
            ];
        }

        if ($descriptionLength < 140 || $motivationLength < 60) {
            return [
                'status' => 'warning',
                'message' => "Le dossier est recevable, mais la presentation de l'activite reste assez legere.",
            ];
        }

        return [
            'status' => 'passed',
            'message' => "Description d'activite et motivation suffisamment detaillees.",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function screenContactCompleteness(OrganizerRequest $organizerRequest): array
    {
        $hasWebsite = null !== $organizerRequest->getWebsiteUrl();
        $hasSocialLinks = null !== $organizerRequest->getSocialLinks();
        $eventTypeCount = count($organizerRequest->getEventTypes());

        if (!$hasWebsite && !$hasSocialLinks) {
            return [
                'status' => 'warning',
                'message' => 'Aucun site web ni reseau social fourni. La demande reste a verifier manuellement.',
                'eventTypeCount' => $eventTypeCount,
            ];
        }

        return [
            'status' => 'passed',
            'message' => 'Canaux de contact complementaires fournis.',
            'eventTypeCount' => $eventTypeCount,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $checks
     */
    private function hasCheckStatus(array $checks, string $status): bool
    {
        foreach ($checks as $check) {
            if (($check['status'] ?? null) === $status) {
                return true;
            }
        }

        return false;
    }

    private function passesLuhn(string $value): bool
    {
        $sum = 0;
        $isEven = false;

        for ($index = strlen($value) - 1; $index >= 0; --$index) {
            $digit = (int) $value[$index];

            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $isEven = !$isEven;
        }

        return 0 === $sum % 10;
    }
}
