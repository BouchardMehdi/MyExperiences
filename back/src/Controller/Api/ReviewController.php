<?php

namespace App\Controller\Api;

use App\Api\ReviewApiPresenter;
use App\Dto\Review\CreateReviewInput;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ExperienceRepository;
use App\Repository\ReviewRepository;
use App\Service\ReviewEligibilityService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/experiences/{experienceId<\d+>}/reviews', name: 'api_experience_reviews_')]
class ReviewController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        int $experienceId,
        Request $request,
        ValidatorInterface $validator,
        ExperienceRepository $experienceRepository,
        ReviewRepository $reviewRepository,
        ReviewEligibilityService $reviewEligibilityService,
        ReviewApiPresenter $reviewApiPresenter,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $experience = $experienceRepository->findPublishedById($experienceId);
        if (!$experience) {
            return $this->json([
                'error' => [
                    'code' => 'experience_not_found',
                    'message' => 'Experience not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $existingReview = $reviewRepository->findOneForUserAndExperience($user, $experience);
        if ($existingReview) {
            return $this->json([
                'error' => [
                    'code' => 'review_already_exists',
                    'message' => 'You have already submitted a review for this experience.',
                ],
            ], Response::HTTP_CONFLICT);
        }

        if (!$reviewEligibilityService->canReview($user, $experience)) {
            return $this->json([
                'error' => [
                    'code' => 'review_not_allowed',
                    'message' => 'You can review an experience only after participating in it.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new CreateReviewInput();
        $input->rating = $this->normalizeInteger($payload['rating'] ?? null, 0);
        $input->comment = $this->normalizeString($payload['comment'] ?? null);

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

        $review = (new Review())
            ->setUser($user)
            ->setExperience($experience)
            ->setRating($input->rating)
            ->setComment($input->comment);

        $entityViolations = $this->collectValidationErrors($validator->validate($review));
        if ([] !== $entityViolations) {
            return $this->json([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'The submitted data is invalid.',
                    'fields' => $entityViolations,
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $entityManager->persist($review);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->json([
                'error' => [
                    'code' => 'review_already_exists',
                    'message' => 'You have already submitted a review for this experience.',
                ],
            ], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'data' => $reviewApiPresenter->present($review),
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

    private function normalizeInteger(mixed $value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    private function normalizeString(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        return $default;
    }
}
