<?php

namespace App\Service;

class OrganizerSiretLookupService
{
    private const SEARCH_ENDPOINT = 'https://recherche-entreprises.api.gouv.fr/search';

    /**
     * @return array<string, mixed>
     */
    public function verify(string $siret, string $organizationName, string $postalCode, string $city): array
    {
        $normalizedSiret = preg_replace('/\D+/', '', $siret);

        if (!is_string($normalizedSiret) || 14 !== strlen($normalizedSiret)) {
            return [
                'status' => 'failed',
                'message' => 'Le SIRET doit contenir exactement 14 chiffres.',
            ];
        }

        $url = sprintf(
            '%s?q=%s&page=1&per_page=10',
            self::SEARCH_ENDPOINT,
            rawurlencode($normalizedSiret)
        );

        $response = $this->fetchJson($url);
        if (!$response['ok']) {
            return [
                'status' => 'warning',
                'message' => "La verification SIRET officielle est temporairement indisponible. Le dossier reste a revoir.",
            ];
        }

        $match = $this->findSiretMatch($response['payload'], $normalizedSiret);
        if (null === $match) {
            return [
                'status' => 'failed',
                'message' => 'Aucun etablissement public ne correspond a ce SIRET dans la base officielle.',
            ];
        }

        $officialName = $this->findString($match, ['nom_complet', 'nom_raison_sociale', 'denomination', 'nom']);
        $officialPostalCode = $this->findString($match, ['code_postal', 'postal_code']);
        $officialCity = $this->findString($match, ['libelle_commune', 'ville', 'commune']);
        $administrativeState = $this->findString($match, ['etat_administratif', 'etatAdministratif', 'statut']);

        if (null !== $administrativeState && $this->isClosedState($administrativeState)) {
            return [
                'status' => 'failed',
                'message' => 'Le SIRET correspond a un etablissement signale comme ferme ou inactif.',
                'officialName' => $officialName,
                'officialPostalCode' => $officialPostalCode,
                'officialCity' => $officialCity,
            ];
        }

        $warnings = [];

        if (
            null !== $officialName
            && !$this->looksLikeSameOrganization($organizationName, $officialName)
        ) {
            $warnings[] = sprintf(
                'Le nom saisi semble differer du nom officiel trouve pour ce SIRET (%s).',
                $officialName
            );
        }

        if (
            null !== $officialPostalCode
            && '' !== trim($postalCode)
            && $this->normalizeComparable($postalCode) !== $this->normalizeComparable($officialPostalCode)
        ) {
            $warnings[] = sprintf(
                'Le code postal saisi (%s) ne correspond pas au code postal officiel (%s).',
                trim($postalCode),
                $officialPostalCode
            );
        }

        if (
            null !== $officialCity
            && '' !== trim($city)
            && $this->normalizeComparable($city) !== $this->normalizeComparable($officialCity)
        ) {
            $warnings[] = sprintf(
                'La ville saisie (%s) ne correspond pas a la ville officielle (%s).',
                trim($city),
                $officialCity
            );
        }

        if ([] !== $warnings) {
            return [
                'status' => 'warning',
                'message' => implode(' ', $warnings),
                'officialName' => $officialName,
                'officialPostalCode' => $officialPostalCode,
                'officialCity' => $officialCity,
            ];
        }

        return [
            'status' => 'passed',
            'message' => sprintf(
                'SIRET retrouve dans la base officielle%s.',
                null !== $officialName ? sprintf(' pour %s', $officialName) : ''
            ),
            'officialName' => $officialName,
            'officialPostalCode' => $officialPostalCode,
            'officialCity' => $officialCity,
        ];
    }

    /**
     * @return array{ok: bool, payload: array<string, mixed>}
     */
    private function fetchJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 4,
                'ignore_errors' => true,
                'header' => implode("\r\n", [
                    'Accept: application/json',
                    'User-Agent: MyExperiences/1.0',
                ]),
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        $statusCode = $this->extractStatusCode($http_response_header ?? []);

        if (false === $responseBody || 200 !== $statusCode) {
            return [
                'ok' => false,
                'payload' => [],
            ];
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($responseBody, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [
                'ok' => false,
                'payload' => [],
            ];
        }

        return [
            'ok' => true,
            'payload' => $payload,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    private function findSiretMatch(array $payload, string $siret): ?array
    {
        $candidates = $this->collectAssociativeArrays($payload);

        foreach ($candidates as $candidate) {
            $candidateSiret = $this->findString($candidate, ['siret']);
            if (null !== $candidateSiret && preg_replace('/\D+/', '', $candidateSiret) === $siret) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    private function collectAssociativeArrays(array $payload): array
    {
        $results = [];

        if ($this->isAssociativeArray($payload)) {
            $results[] = $payload;
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                foreach ($this->collectAssociativeArrays($value) as $nested) {
                    $results[] = $nested;
                }
            }
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $keys
     */
    private function findString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && is_scalar($payload[$key])) {
                $value = trim((string) $payload[$key]);
                if ('' !== $value) {
                    return $value;
                }
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $nested = $this->findString($value, $keys);
                if (null !== $nested) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function looksLikeSameOrganization(string $submittedName, string $officialName): bool
    {
        $left = $this->normalizeComparable($submittedName);
        $right = $this->normalizeComparable($officialName);

        if ('' === $left || '' === $right) {
            return true;
        }

        if ($left === $right || str_contains($left, $right) || str_contains($right, $left)) {
            return true;
        }

        similar_text($left, $right, $similarity);

        return $similarity >= 72;
    }

    private function isClosedState(string $value): bool
    {
        $normalized = $this->normalizeComparable($value);

        return in_array($normalized, ['f', 'ferme', 'fermee', 'closed', 'inactive'], true);
    }

    /**
     * @param list<string> $headers
     */
    private function extractStatusCode(array $headers): ?int
    {
        foreach ($headers as $header) {
            if (!is_string($header)) {
                continue;
            }

            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function normalizeComparable(string $value): string
    {
        $normalized = trim(mb_strtolower($value));
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        if (false === $ascii) {
            $ascii = $normalized;
        }

        $ascii = preg_replace('/[^a-z0-9]/', '', $ascii);

        return is_string($ascii) ? $ascii : '';
    }

    /**
     * @param array<mixed> $value
     */
    private function isAssociativeArray(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }
}
