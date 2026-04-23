<?php

namespace App\Controller\Api;

use App\Api\AdminApiPresenter;
use App\Entity\Experience;
use App\Entity\Review;
use App\Entity\User;
use App\Enum\ExperienceStatus;
use App\Repository\ExperienceRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin', name: 'api_admin_')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepository,
        ExperienceRepository $experienceRepository,
        ReviewRepository $reviewRepository,
        AdminApiPresenter $adminApiPresenter,
    ): JsonResponse {
        $admin = $this->getAdminUser();
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $users = $userRepository->findAllDetailed();
        $experiences = $experienceRepository->findAllDetailed();
        $reviews = $reviewRepository->findAllDetailed();

        return $this->json([
            'data' => $adminApiPresenter->presentDashboard($users, $experiences, $reviews),
        ]);
    }

    #[Route('/users/{id<\d+>}', name: 'users_update', methods: ['PATCH'])]
    public function usersUpdate(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        AdminApiPresenter $adminApiPresenter,
    ): JsonResponse {
        $admin = $this->getAdminUser();
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $user = $userRepository->findDetailedById($id);
        if (!$user) {
            return $this->createNotFoundResponse('user_not_found', 'User not found.');
        }

        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $fieldErrors = $this->applyUserPayload($user, $payload);
        if ([] !== $fieldErrors) {
            return $this->createValidationResponse($fieldErrors);
        }

        $entityViolations = $this->collectValidationErrors($validator->validate($user));
        if ([] !== $entityViolations) {
            return $this->createValidationResponse($entityViolations);
        }

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->createValidationResponse([
                'email' => ['A user already exists with this email address.'],
            ]);
        }

        return $this->json([
            'data' => $adminApiPresenter->presentUser($user),
        ]);
    }

    #[Route('/experiences/{id<\d+>}', name: 'experiences_update', methods: ['PATCH'])]
    public function experiencesUpdate(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        ExperienceRepository $experienceRepository,
        EntityManagerInterface $entityManager,
        AdminApiPresenter $adminApiPresenter,
    ): JsonResponse {
        $admin = $this->getAdminUser();
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $experience = $experienceRepository->findDetailedById($id);
        if (!$experience) {
            return $this->createNotFoundResponse('experience_not_found', 'Experience not found.');
        }

        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $fieldErrors = $this->applyExperiencePayload($experience, $payload);
        if ([] !== $fieldErrors) {
            return $this->createValidationResponse($fieldErrors);
        }

        $entityViolations = $this->collectValidationErrors($validator->validate($experience));
        if ([] !== $entityViolations) {
            return $this->createValidationResponse($entityViolations);
        }

        $entityManager->flush();

        return $this->json([
            'data' => $adminApiPresenter->presentExperience($experience),
        ]);
    }

    #[Route('/experiences/{id<\d+>}', name: 'experiences_delete', methods: ['DELETE'])]
    public function experiencesDelete(
        int $id,
        ExperienceRepository $experienceRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $admin = $this->getAdminUser();
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $experience = $experienceRepository->findDetailedById($id);
        if (!$experience) {
            return $this->createNotFoundResponse('experience_not_found', 'Experience not found.');
        }

        $entityManager->remove($experience);
        $entityManager->flush();

        return $this->json([
            'data' => [
                'id' => $id,
                'deleted' => true,
            ],
        ]);
    }

    #[Route('/reviews/{id<\d+>}', name: 'reviews_delete', methods: ['DELETE'])]
    public function reviewsDelete(
        int $id,
        ReviewRepository $reviewRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $admin = $this->getAdminUser();
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $review = $reviewRepository->findDetailedById($id);
        if (!$review) {
            return $this->createNotFoundResponse('review_not_found', 'Review not found.');
        }

        $entityManager->remove($review);
        $entityManager->flush();

        return $this->json([
            'data' => [
                'id' => $id,
                'deleted' => true,
            ],
        ]);
    }

    /**
     * @return User|JsonResponse
     */
    private function getAdminUser(): User|JsonResponse
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

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => [
                    'code' => 'admin_role_required',
                    'message' => 'Admin access is required to access this resource.',
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
    private function applyUserPayload(User $user, array $payload): array
    {
        $errors = [];

        if (array_key_exists('email', $payload)) {
            $email = $this->normalizeString($payload['email'] ?? null);
            if ('' === $email) {
                $errors['email'][] = 'The email field is required.';
            } else {
                $user->setEmail($email);
            }
        }

        if (array_key_exists('firstName', $payload)) {
            $firstName = $this->normalizeString($payload['firstName'] ?? null);
            if ('' === $firstName) {
                $errors['firstName'][] = 'The firstName field is required.';
            } else {
                $user->setFirstname($firstName);
            }
        }

        if (array_key_exists('lastName', $payload)) {
            $lastName = $this->normalizeString($payload['lastName'] ?? null);
            if ('' === $lastName) {
                $errors['lastName'][] = 'The lastName field is required.';
            } else {
                $user->setLastname($lastName);
            }
        }

        if (array_key_exists('roles', $payload)) {
            $roles = $this->normalizeRoles($payload['roles']);
            if (null === $roles) {
                $errors['roles'][] = 'The roles field must contain only ROLE_USER, ROLE_ORGANIZER or ROLE_ADMIN.';
            } else {
                $user->setRoles($roles);
            }
        }

        return $errors;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function applyExperiencePayload(Experience $experience, array $payload): array
    {
        $errors = [];

        if (array_key_exists('title', $payload)) {
            $title = $this->normalizeString($payload['title'] ?? null);
            if ('' === $title) {
                $errors['title'][] = 'The title field is required.';
            } else {
                $experience->setTitle($title);
            }
        }

        if (array_key_exists('description', $payload)) {
            $description = $this->normalizeString($payload['description'] ?? null);
            if ('' === $description) {
                $errors['description'][] = 'The description field is required.';
            } else {
                $experience->setDescription($description);
            }
        }

        if (array_key_exists('location', $payload)) {
            $location = $this->normalizeString($payload['location'] ?? null);
            if ('' === $location) {
                $errors['location'][] = 'The location field is required.';
            } else {
                $experience->setLocation($location);
            }
        }

        if (array_key_exists('price', $payload)) {
            $price = $this->normalizeDecimal($payload['price'] ?? null);
            if (null === $price) {
                $errors['price'][] = 'The price field must be numeric.';
            } else {
                $experience->setPrice($price);
            }
        }

        if (array_key_exists('durationMinutes', $payload)) {
            $duration = $this->normalizeInteger($payload['durationMinutes'] ?? null);
            if (null === $duration) {
                $errors['durationMinutes'][] = 'The durationMinutes field must be an integer.';
            } else {
                $experience->setDuration($duration);
            }
        }

        if (array_key_exists('status', $payload)) {
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

    /**
     * @return list<string>|null
     */
    private function normalizeRoles(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $allowedRoles = ['ROLE_USER', 'ROLE_ORGANIZER', 'ROLE_ADMIN'];
        $roles = [];

        foreach ($value as $role) {
            if (!is_string($role)) {
                return null;
            }

            $normalized = strtoupper(trim($role));
            if (!in_array($normalized, $allowedRoles, true)) {
                return null;
            }

            if ('ROLE_USER' !== $normalized) {
                $roles[] = $normalized;
            }
        }

        return array_values(array_unique($roles));
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

    private function normalizeEnum(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return '' === $normalized ? null : $normalized;
    }
}
