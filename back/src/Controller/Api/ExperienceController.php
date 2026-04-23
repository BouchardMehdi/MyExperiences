<?php

namespace App\Controller\Api;

use App\Api\ExperienceApiPresenter;
use App\Entity\Experience;
use App\Entity\User;
use App\Repository\ExperienceRepository;
use App\Repository\ReviewRepository;
use App\Repository\SlotRepository;
use App\Service\ReviewEligibilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/experiences', name: 'api_experiences_')]
class ExperienceController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ExperienceRepository $experienceRepository,
        ExperienceApiPresenter $experienceApiPresenter,
    ): JsonResponse {
        $filters = $this->parseFilters($request);

        if ($filters instanceof JsonResponse) {
            return $filters;
        }

        $experiences = $experienceRepository->findPublishedWithFilters(
            $filters['location'],
            $filters['maxPrice'],
            $filters['date']
        );

        return $this->json([
            'data' => $experienceApiPresenter->presentList($experiences),
            'meta' => [
                'total' => count($experiences),
                'filters' => [
                    'location' => $filters['location'],
                    'maxPrice' => null === $filters['maxPrice'] ? null : number_format($filters['maxPrice'], 2, '.', ''),
                    'date' => $filters['date']?->format('Y-m-d'),
                ],
            ],
        ]);
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(
        int $id,
        ExperienceRepository $experienceRepository,
        SlotRepository $slotRepository,
        ReviewRepository $reviewRepository,
        ReviewEligibilityService $reviewEligibilityService,
        ExperienceApiPresenter $experienceApiPresenter,
    ): JsonResponse {
        $experience = $experienceRepository->findPublishedById($id);

        if (!$experience) {
            return $this->json([
                'error' => [
                    'code' => 'experience_not_found',
                    'message' => 'Experience not found.',
                ],
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $bookableSlots = $slotRepository->findBookableForExperience($experience);
        $latestReviews = $reviewRepository->findLatestForExperience($experience);
        $reviewPolicy = $this->buildReviewPolicy(
            $this->getUser(),
            $experience,
            $reviewRepository,
            $reviewEligibilityService,
            $experienceApiPresenter,
        );

        return $this->json([
            'data' => $experienceApiPresenter->presentDetail($experience, $bookableSlots, $latestReviews, $reviewPolicy),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReviewPolicy(
        mixed $user,
        Experience $experience,
        ReviewRepository $reviewRepository,
        ReviewEligibilityService $reviewEligibilityService,
        ExperienceApiPresenter $experienceApiPresenter,
    ): array {
        if (!$user instanceof User) {
            return [
                'isAuthenticated' => false,
                'canCreate' => false,
                'status' => 'anonymous',
                'userReview' => null,
            ];
        }

        $userReview = $reviewRepository->findOneForUserAndExperience($user, $experience);

        if ($userReview) {
            return [
                'isAuthenticated' => true,
                'canCreate' => false,
                'status' => 'already_reviewed',
                'userReview' => $experienceApiPresenter->presentReview($userReview),
            ];
        }

        if ($reviewEligibilityService->canReview($user, $experience)) {
            return [
                'isAuthenticated' => true,
                'canCreate' => true,
                'status' => 'can_review',
                'userReview' => null,
            ];
        }

        return [
            'isAuthenticated' => true,
            'canCreate' => false,
            'status' => 'not_eligible',
            'userReview' => null,
        ];
    }

    /**
     * @return array{location: ?string, maxPrice: ?float, date: ?\DateTimeImmutable}|JsonResponse
     */
    private function parseFilters(Request $request): array|JsonResponse
    {
        $location = $this->normalizeString($request->query->get('location'));
        $maxPrice = $request->query->get('maxPrice', $request->query->get('price'));
        $date = $request->query->get('date');

        if (null !== $maxPrice && '' !== $maxPrice) {
            if (!is_scalar($maxPrice) || !is_numeric((string) $maxPrice)) {
                return $this->createValidationErrorResponse('maxPrice', 'The maxPrice filter must be numeric.');
            }

            $maxPrice = (float) $maxPrice;
            if ($maxPrice < 0) {
                return $this->createValidationErrorResponse('maxPrice', 'The maxPrice filter must be greater than or equal to 0.');
            }
        } else {
            $maxPrice = null;
        }

        if (null !== $date && '' !== $date) {
            if (!is_scalar($date)) {
                return $this->createValidationErrorResponse('date', 'The date filter must use the YYYY-MM-DD format.');
            }

            $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $date);
            $errors = \DateTimeImmutable::getLastErrors();

            if (
                false === $parsedDate
                || false !== $errors && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)
            ) {
                return $this->createValidationErrorResponse('date', 'The date filter must use the YYYY-MM-DD format.');
            }

            $date = $parsedDate;
        } else {
            $date = null;
        }

        return [
            'location' => $location,
            'maxPrice' => $maxPrice,
            'date' => $date,
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return '' === $normalized ? null : $normalized;
    }

    private function createValidationErrorResponse(string $field, string $message): JsonResponse
    {
        return $this->json([
            'error' => [
                'code' => 'invalid_query_parameter',
                'message' => $message,
                'field' => $field,
            ],
        ], JsonResponse::HTTP_BAD_REQUEST);
    }
}
