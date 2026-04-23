<?php

namespace App\Controller\Api;

use App\Api\OrganizerApiPresenter;
use App\Entity\Experience;
use App\Entity\Slot;
use App\Entity\User;
use App\Enum\ExperienceStatus;
use App\Repository\BookingRepository;
use App\Repository\ExperienceRepository;
use App\Repository\SlotRepository;
use App\Security\ExperienceVoter;
use App\Security\SlotVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/organizer', name: 'api_organizer_')]
class OrganizerController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        ExperienceRepository $experienceRepository,
        SlotRepository $slotRepository,
        BookingRepository $bookingRepository,
        OrganizerApiPresenter $organizerApiPresenter,
    ): JsonResponse {
        $organizer = $this->getOrganizerUser();
        if ($organizer instanceof JsonResponse) {
            return $organizer;
        }

        $experiences = $experienceRepository->findDetailedForOrganizer($organizer);
        $slots = $slotRepository->findForOrganizer($organizer);
        $bookings = $bookingRepository->findForOrganizer($organizer);

        return $this->json([
            'data' => $organizerApiPresenter->presentDashboard($experiences, $bookings, $slots),
        ]);
    }

    #[Route('/experiences', name: 'experiences_index', methods: ['GET'])]
    public function experiencesIndex(
        ExperienceRepository $experienceRepository,
        OrganizerApiPresenter $organizerApiPresenter,
    ): JsonResponse {
        $organizer = $this->getOrganizerUser();
        if ($organizer instanceof JsonResponse) {
            return $organizer;
        }

        return $this->json([
            'data' => $organizerApiPresenter->presentExperiences($experienceRepository->findDetailedForOrganizer($organizer)),
        ]);
    }

    #[Route('/experiences', name: 'experiences_create', methods: ['POST'])]
    public function experiencesCreate(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        OrganizerApiPresenter $organizerApiPresenter,
    ): JsonResponse {
        $organizer = $this->getOrganizerUser();
        if ($organizer instanceof JsonResponse) {
            return $organizer;
        }

        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $experience = (new Experience())->setOrganizer($organizer);
        $fieldErrors = $this->applyExperiencePayload($experience, $payload, false);
        if ([] !== $fieldErrors) {
            return $this->createValidationResponse($fieldErrors);
        }

        $entityViolations = $this->collectValidationErrors($validator->validate($experience));
        if ([] !== $entityViolations) {
            return $this->createValidationResponse($entityViolations);
        }

        $entityManager->persist($experience);
        $entityManager->flush();

        return $this->json([
            'data' => $organizerApiPresenter->presentExperience($experience),
        ], Response::HTTP_CREATED);
    }

    #[Route('/experiences/{id<\d+>}', name: 'experiences_update', methods: ['PATCH'])]
    public function experiencesUpdate(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        ExperienceRepository $experienceRepository,
        EntityManagerInterface $entityManager,
        OrganizerApiPresenter $organizerApiPresenter,
    ): JsonResponse {
        $organizer = $this->getOrganizerUser();
        if ($organizer instanceof JsonResponse) {
            return $organizer;
        }

        $experience = $experienceRepository->findOneForOrganizer($id, $organizer);
        if (!$experience) {
            return $this->createNotFoundResponse('experience_not_found', 'Experience not found.');
        }

        $this->denyAccessUnlessGranted(ExperienceVoter::MANAGE, $experience);

        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $fieldErrors = $this->applyExperiencePayload($experience, $payload, true);
        if ([] !== $fieldErrors) {
            return $this->createValidationResponse($fieldErrors);
        }

        $entityViolations = $this->collectValidationErrors($validator->validate($experience));
        if ([] !== $entityViolations) {
            return $this->createValidationResponse($entityViolations);
        }

        $entityManager->flush();

        return $this->json([
            'data' => $organizerApiPresenter->presentExperience($experience),
        ]);
    }

    #[Route('/experiences/{id<\d+>}', name: 'experiences_delete', methods: ['DELETE'])]
    public function experiencesDelete(
        int $id,
        ExperienceRepository $experienceRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $organizer = $this->getOrganizerUser();
        if ($organizer instanceof JsonResponse) {
            return $organizer;
        }

        $experience = $experienceRepository->findOneForOrganizer($id, $organizer);
        if (!$experience) {
            return $this->createNotFoundResponse('experience_not_found', 'Experience not found.');
        }

        $this->denyAccessUnlessGranted(ExperienceVoter::MANAGE, $experience);

        $entityManager->remove($experience);
        $entityManager->flush();

        return $this->json([
            'data' => [
                'id' => $id,
                'deleted' => true,
            ],
        ]);
    }

    #[Route('/experiences/{id<\d+>}/slots', name: 'slots_create', methods: ['POST'])]
    public function slotsCreate(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        ExperienceRepository $experienceRepository,
        EntityManagerInterface $entityManager,
        OrganizerApiPresenter $organizerApiPresenter,
    ): JsonResponse {
        $organizer = $this->getOrganizerUser();
        if ($organizer instanceof JsonResponse) {
            return $organizer;
        }

        $experience = $experienceRepository->findOneForOrganizer($id, $organizer);
        if (!$experience) {
            return $this->createNotFoundResponse('experience_not_found', 'Experience not found.');
        }

        $this->denyAccessUnlessGranted(ExperienceVoter::MANAGE, $experience);

        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $slot = (new Slot())->setExperience($experience);
        $fieldErrors = $this->applySlotPayload($slot, $payload, false);
        if ([] !== $fieldErrors) {
            return $this->createValidationResponse($fieldErrors);
        }

        $entityViolations = $this->collectValidationErrors($validator->validate($slot));
        if ([] !== $entityViolations) {
            return $this->createValidationResponse($entityViolations);
        }

        $entityManager->persist($slot);
        $entityManager->flush();

        return $this->json([
            'data' => $organizerApiPresenter->presentSlot($slot),
        ], Response::HTTP_CREATED);
    }

    #[Route('/slots/{id<\d+>}', name: 'slots_update', methods: ['PATCH'])]
    public function slotsUpdate(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        SlotRepository $slotRepository,
        EntityManagerInterface $entityManager,
        OrganizerApiPresenter $organizerApiPresenter,
    ): JsonResponse {
        $organizer = $this->getOrganizerUser();
        if ($organizer instanceof JsonResponse) {
            return $organizer;
        }

        $slot = $slotRepository->findOneForOrganizer($id, $organizer);
        if (!$slot) {
            return $this->createNotFoundResponse('slot_not_found', 'Slot not found.');
        }

        $this->denyAccessUnlessGranted(SlotVoter::MANAGE, $slot);

        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $fieldErrors = $this->applySlotPayload($slot, $payload, true);
        if ([] !== $fieldErrors) {
            return $this->createValidationResponse($fieldErrors);
        }

        $entityViolations = $this->collectValidationErrors($validator->validate($slot));
        if ([] !== $entityViolations) {
            return $this->createValidationResponse($entityViolations);
        }

        $entityManager->flush();

        return $this->json([
            'data' => $organizerApiPresenter->presentSlot($slot),
        ]);
    }

    #[Route('/slots/{id<\d+>}', name: 'slots_delete', methods: ['DELETE'])]
    public function slotsDelete(
        int $id,
        SlotRepository $slotRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $organizer = $this->getOrganizerUser();
        if ($organizer instanceof JsonResponse) {
            return $organizer;
        }

        $slot = $slotRepository->findOneForOrganizer($id, $organizer);
        if (!$slot) {
            return $this->createNotFoundResponse('slot_not_found', 'Slot not found.');
        }

        $this->denyAccessUnlessGranted(SlotVoter::MANAGE, $slot);

        $entityManager->remove($slot);
        $entityManager->flush();

        return $this->json([
            'data' => [
                'id' => $id,
                'deleted' => true,
            ],
        ]);
    }

    #[Route('/bookings', name: 'bookings_index', methods: ['GET'])]
    public function bookingsIndex(
        BookingRepository $bookingRepository,
        OrganizerApiPresenter $organizerApiPresenter,
    ): JsonResponse {
        $organizer = $this->getOrganizerUser();
        if ($organizer instanceof JsonResponse) {
            return $organizer;
        }

        return $this->json([
            'data' => $organizerApiPresenter->presentBookings($bookingRepository->findForOrganizer($organizer)),
        ]);
    }

    /**
     * @return User|JsonResponse
     */
    private function getOrganizerUser(): User|JsonResponse
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

        if (!$this->isGranted('ROLE_ORGANIZER') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => [
                    'code' => 'organizer_role_required',
                    'message' => 'Organizer access is required to access this resource.',
                ],
            ], Response::HTTP_FORBIDDEN);
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

    /**
     * @return array<string, array<int, string>>
     */
    private function applyExperiencePayload(Experience $experience, array $payload, bool $partial): array
    {
        $errors = [];

        if (!$partial || array_key_exists('title', $payload)) {
            $title = $this->normalizeString($payload['title'] ?? null);
            if ('' === $title) {
                $errors['title'][] = 'The title field is required.';
            } else {
                $experience->setTitle($title);
            }
        }

        if (!$partial || array_key_exists('description', $payload)) {
            $description = $this->normalizeString($payload['description'] ?? null);
            if ('' === $description) {
                $errors['description'][] = 'The description field is required.';
            } else {
                $experience->setDescription($description);
            }
        }

        if (!$partial || array_key_exists('location', $payload)) {
            $location = $this->normalizeString($payload['location'] ?? null);
            if ('' === $location) {
                $errors['location'][] = 'The location field is required.';
            } else {
                $experience->setLocation($location);
            }
        }

        if (!$partial || array_key_exists('price', $payload)) {
            $price = $this->normalizeDecimal($payload['price'] ?? null);
            if (null === $price) {
                $errors['price'][] = 'The price field must be numeric.';
            } else {
                $experience->setPrice($price);
            }
        }

        if (!$partial || array_key_exists('durationMinutes', $payload)) {
            $duration = $this->normalizeInteger($payload['durationMinutes'] ?? null);
            if (null === $duration) {
                $errors['durationMinutes'][] = 'The durationMinutes field must be an integer.';
            } else {
                $experience->setDuration($duration);
            }
        }

        if (!$partial || array_key_exists('status', $payload)) {
            $status = $this->normalizeEnum($payload['status'] ?? null);
            $statusEnum = null === $status ? null : ExperienceStatus::tryFrom($status);

            if (!$statusEnum) {
                $errors['status'][] = 'The status field must be DRAFT, PUBLISHED or ARCHIVED.';
            } else {
                $experience->setStatus($statusEnum);
            }
        }

        return $errors;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function applySlotPayload(Slot $slot, array $payload, bool $partial): array
    {
        $errors = [];

        if (!$partial || array_key_exists('startAt', $payload)) {
            $startAt = $this->parseDateTime($payload['startAt'] ?? null);
            if (!$startAt) {
                $errors['startAt'][] = 'The startAt field must contain a valid date-time.';
            } else {
                $slot->setStartAt($startAt);
            }
        }

        if (!$partial || array_key_exists('endAt', $payload)) {
            $endAt = $this->parseDateTime($payload['endAt'] ?? null);
            if (!$endAt) {
                $errors['endAt'][] = 'The endAt field must contain a valid date-time.';
            } else {
                $slot->setEndAt($endAt);
            }
        }

        if (!$partial || array_key_exists('capacity', $payload)) {
            $capacity = $this->normalizeInteger($payload['capacity'] ?? null);
            if (null === $capacity) {
                $errors['capacity'][] = 'The capacity field must be an integer.';
            } else {
                $slot->setCapacity($capacity);
            }
        }

        if (!$partial || array_key_exists('isActive', $payload)) {
            $isActive = $this->normalizeBoolean($payload['isActive'] ?? null);
            if (null === $isActive) {
                $errors['isActive'][] = 'The isActive field must be a boolean.';
            } else {
                $slot->setIsActive($isActive);
            }
        }

        return $errors;
    }

    /**
     * @param iterable<ConstraintViolationInterface> $violations
     * @return array<string, array<int, string>>
     */
    private function collectValidationErrors(iterable $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $field = '' === $violation->getPropertyPath() ? '_global' : $violation->getPropertyPath();
            $errors[$field] ??= [];
            $errors[$field][] = $violation->getMessage();
        }

        return $errors;
    }

    /**
     * @param array<string, array<int, string>> $errors
     */
    private function createValidationResponse(array $errors): JsonResponse
    {
        return $this->json([
            'error' => [
                'code' => 'validation_failed',
                'message' => 'The submitted data is invalid.',
                'fields' => $errors,
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function createNotFoundResponse(string $code, string $message): JsonResponse
    {
        return $this->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], Response::HTTP_NOT_FOUND);
    }

    private function normalizeString(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function normalizeDecimal(mixed $value): ?string
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return number_format((float) $value, 2, '.', '');
        }

        return null;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        return null;
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                'true', '1' => true,
                'false', '0' => false,
                default => null,
            };
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        return null;
    }

    private function normalizeEnum(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return '' === $normalized ? null : $normalized;
    }

    private function parseDateTime(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable(trim($value));
        } catch (\Exception) {
            return null;
        }
    }
}
