<?php

namespace App\Service;

class OrganizerAddressLookupService
{
    private const SEARCH_ENDPOINT = 'https://data.geopf.fr/geocodage/search';

    /**
     * @return array<string, mixed>
     */
    public function verify(string $streetAddress, string $postalCode, string $city, string $country): array
    {
        $query = trim(sprintf('%s, %s %s, %s', $streetAddress, $postalCode, $city, $country));
        if ('' === $query) {
            return [
                'status' => 'failed',
                'message' => "L'adresse est incomplete.",
            ];
        }

        $url = sprintf('%s?limit=1&q=%s', self::SEARCH_ENDPOINT, rawurlencode($query));
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
                'status' => 'warning',
                'message' => "La verification d'adresse est temporairement indisponible. La demande reste a revoir manuellement.",
            ];
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($responseBody, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [
                'status' => 'warning',
                'message' => "La reponse du service d'adresse est inexploitable pour le moment.",
            ];
        }

        $features = $payload['features'] ?? null;
        if (!is_array($features) || [] === $features) {
            return [
                'status' => 'failed',
                'message' => "L'adresse n'a pas ete retrouvee dans le referentiel public.",
            ];
        }

        $feature = $features[0];
        if (!is_array($feature)) {
            return [
                'status' => 'warning',
                'message' => "L'adresse a retourne un format inattendu et doit etre revue.",
            ];
        }

        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
        $geometry = is_array($feature['geometry'] ?? null) ? $feature['geometry'] : [];
        $coordinates = is_array($geometry['coordinates'] ?? null) ? $geometry['coordinates'] : [];

        $score = is_numeric($properties['score'] ?? null) ? (float) $properties['score'] : null;
        $matchedPostalCode = $this->normalizeComparable((string) ($properties['postcode'] ?? ''));
        $matchedCity = $this->normalizeComparable((string) ($properties['city'] ?? ''));
        $expectedPostalCode = $this->normalizeComparable($postalCode);
        $expectedCity = $this->normalizeComparable($city);

        $postalMatches = '' !== $expectedPostalCode && '' !== $matchedPostalCode && $expectedPostalCode === $matchedPostalCode;
        $cityMatches = '' !== $expectedCity && '' !== $matchedCity && $expectedCity === $matchedCity;
        $matchedLabel = trim((string) ($properties['label'] ?? 'Adresse trouvee'));

        if (null !== $score && $score >= 0.75 && ($postalMatches || $cityMatches)) {
            return [
                'status' => 'passed',
                'message' => sprintf('Adresse confirmee via le geocodeur public : %s.', $matchedLabel),
                'matchedLabel' => $matchedLabel,
                'score' => $score,
                'latitude' => isset($coordinates[1]) && is_numeric($coordinates[1]) ? (float) $coordinates[1] : null,
                'longitude' => isset($coordinates[0]) && is_numeric($coordinates[0]) ? (float) $coordinates[0] : null,
            ];
        }

        if (null !== $score && $score < 0.45) {
            return [
                'status' => 'failed',
                'message' => "L'adresse semble incorrecte ou trop imprecise pour etre validee automatiquement.",
                'matchedLabel' => $matchedLabel,
                'score' => $score,
            ];
        }

        return [
            'status' => 'warning',
            'message' => sprintf("L'adresse a ete partiellement reconnue (%s) mais demande une verification manuelle.", $matchedLabel),
            'matchedLabel' => $matchedLabel,
            'score' => $score,
            'latitude' => isset($coordinates[1]) && is_numeric($coordinates[1]) ? (float) $coordinates[1] : null,
            'longitude' => isset($coordinates[0]) && is_numeric($coordinates[0]) ? (float) $coordinates[0] : null,
        ];
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
}
