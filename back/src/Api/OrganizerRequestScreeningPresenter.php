<?php

namespace App\Api;

use App\Entity\OrganizerRequest;
use App\Enum\OrganizerRequestScreeningStatus;

class OrganizerRequestScreeningPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(OrganizerRequest $organizerRequest): array
    {
        $checks = $organizerRequest->getScreeningChecks();
        $screeningStatus = $organizerRequest->getScreeningStatus();

        return [
            'status' => $screeningStatus->value,
            'label' => $screeningStatus->label(),
            'isAutoRejected' => OrganizerRequestScreeningStatus::AUTO_REJECTED === $screeningStatus,
            'checks' => $this->presentChecks($checks),
            'summary' => $this->buildSummary($checks, $screeningStatus),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $checks
     * @return list<array<string, mixed>>
     */
    private function presentChecks(array $checks): array
    {
        $labels = [
            'phone' => 'Telephone',
            'siret' => 'SIRET',
            'address' => 'Adresse',
            'profile' => 'Dossier',
            'contact' => 'Contact',
        ];

        $presented = [];

        foreach ($checks as $code => $check) {
            if (!is_array($check)) {
                continue;
            }

            $presented[] = [
                'code' => $code,
                'label' => $labels[$code] ?? ucfirst($code),
                'status' => $check['status'] ?? 'warning',
                'message' => $check['message'] ?? 'Controle non detaille.',
                'details' => $check,
            ];
        }

        return $presented;
    }

    /**
     * @param array<string, array<string, mixed>> $checks
     * @return list<string>
     */
    private function buildSummary(array $checks, OrganizerRequestScreeningStatus $screeningStatus): array
    {
        $messages = [];

        foreach ($checks as $check) {
            if (!is_array($check)) {
                continue;
            }

            $status = $check['status'] ?? null;
            if (!in_array($status, ['failed', 'warning'], true)) {
                continue;
            }

            $message = $check['message'] ?? null;
            if (is_string($message) && '' !== trim($message)) {
                $messages[] = trim($message);
            }
        }

        if ([] !== $messages) {
            return array_values(array_unique($messages));
        }

        return match ($screeningStatus) {
            OrganizerRequestScreeningStatus::PRE_VALIDATED => ['Le dossier a passe les controles automatiques de base.'],
            OrganizerRequestScreeningStatus::NEEDS_REVIEW => ['Le dossier est recevable mais demande une verification humaine.'],
            OrganizerRequestScreeningStatus::AUTO_REJECTED => ['Le dossier a ete refuse automatiquement apres les controles de coherence.'],
        };
    }
}
