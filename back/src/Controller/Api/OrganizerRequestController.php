<?php

namespace App\Controller\Api;

use App\Api\OrganizerRequestApiPresenter;
use App\Dto\OrganizerRequest\CreateOrganizerRequestInput;
use App\Entity\OrganizerRequest;
use App\Entity\User;
use App\Repository\OrganizerRequestRepository;
use App\Service\OrganizerRequestScreeningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/organizer-requests', name: 'api_organizer_requests_')]
class OrganizerRequestController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        OrganizerRequestRepository $organizerRequestRepository,
        OrganizerRequestApiPresenter $organizerRequestApiPresenter,
        OrganizerRequestScreeningService $organizerRequestScreeningService,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        if ($user->isOrganizer() || $user->isAdmin()) {
            return $this->json([
                'error' => [
                    'code' => 'already_organizer',
                    'message' => 'This account already has organizer access.',
                ],
            ], Response::HTTP_CONFLICT);
        }

        if ($organizerRequestRepository->findPendingForUser($user)) {
            return $this->json([
                'error' => [
                    'code' => 'organizer_request_pending',
                    'message' => 'An organizer request is already pending for this account.',
                ],
            ], Response::HTTP_CONFLICT);
        }

        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new CreateOrganizerRequestInput();
        $input->organizationName = $this->normalizeString($payload['organizationName'] ?? null);
        $input->phoneNumber = $this->normalizeString($payload['phoneNumber'] ?? null);
        $input->streetAddress = $this->normalizeString($payload['streetAddress'] ?? null);
        $input->postalCode = $this->normalizeString($payload['postalCode'] ?? null);
        $input->city = $this->normalizeString($payload['city'] ?? null);
        $input->country = $this->normalizeString($payload['country'] ?? 'France');
        $input->businessType = $this->normalizeString($payload['businessType'] ?? null);
        $input->eventTypes = $this->normalizeStringArray($payload['eventTypes'] ?? []);
        $input->activityDescription = $this->normalizeString($payload['activityDescription'] ?? null);
        $input->websiteUrl = $this->normalizeString($payload['websiteUrl'] ?? null);
        $input->socialLinks = $this->normalizeString($payload['socialLinks'] ?? null);
        $input->siret = $this->normalizeString($payload['siret'] ?? null);
        $input->motivation = $this->normalizeString($payload['motivation'] ?? null);

        $fieldErrors = $this->collectValidationErrors($validator->validate($input));
        if ([] !== $fieldErrors) {
            return $this->json([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'The submitted data is invalid.',
                    'fields' => $fieldErrors,
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $organizerRequest = (new OrganizerRequest())
            ->setUser($user)
            ->setOrganizationName($input->organizationName)
            ->setPhoneNumber($input->phoneNumber)
            ->setStreetAddress($input->streetAddress)
            ->setPostalCode($input->postalCode)
            ->setCity($input->city)
            ->setCountry($input->country)
            ->setBusinessType($input->businessType)
            ->setEventTypes($input->eventTypes)
            ->setActivityDescription($input->activityDescription)
            ->setWebsiteUrl('' === $input->websiteUrl ? null : $input->websiteUrl)
            ->setSocialLinks('' === $input->socialLinks ? null : $input->socialLinks)
            ->setSiret($input->siret)
            ->setMotivation($input->motivation);

        $organizerRequestScreeningService->screen($organizerRequest);

        $entityManager->persist($organizerRequest);
        $entityManager->flush();

        return $this->json([
            'data' => $organizerRequestApiPresenter->present($organizerRequest),
        ], Response::HTTP_CREATED);
    }

    /**
     * @return User|JsonResponse
     */
    private function getAuthenticatedUser(): User|JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'error' => [
                    'code' => 'authentication_required',
                    'message' => 'Authentication is required to access this resource.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $user;
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function decodeRequestBody(Request $request): array|JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json([
                'error' => [
                    'code' => 'invalid_json',
                    'message' => 'The request body must contain valid JSON.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($payload)) {
            return $this->json([
                'error' => [
                    'code' => 'invalid_payload',
                    'message' => 'The request payload must be a JSON object.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        return $payload;
    }

    private function normalizeString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @return list<string>
     */
    private function normalizeStringArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $candidate = strtoupper(trim((string) $item));
            if ('' !== $candidate) {
                $normalized[] = $candidate;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param iterable<ConstraintViolationInterface> $violations
     * @return array<string, array<int, string>>
     */
    private function collectValidationErrors(iterable $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $errors[$field] ??= [];
            $errors[$field][] = $violation->getMessage();
        }

        return $errors;
    }
}
