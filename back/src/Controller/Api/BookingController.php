<?php

namespace App\Controller\Api;

use App\Api\BookingApiPresenter;
use App\Dto\Booking\CreateBookingInput;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\SlotRepository;
use App\Security\BookingVoter;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/bookings', name: 'api_bookings_')]
class BookingController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        BookingRepository $bookingRepository,
        BookingApiPresenter $bookingApiPresenter,
    ): JsonResponse {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $bookings = $bookingRepository->findForUser($user);

        return $this->json([
            'data' => $bookingApiPresenter->presentList($bookings),
            'meta' => [
                'total' => count($bookings),
            ],
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        SlotRepository $slotRepository,
        BookingService $bookingService,
        EntityManagerInterface $entityManager,
        BookingApiPresenter $bookingApiPresenter,
    ): JsonResponse {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new CreateBookingInput();
        $input->slotId = $this->normalizeInteger($payload['slotId'] ?? null);
        $input->seats = $this->normalizeInteger($payload['seats'] ?? null, 1);

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

        $slot = $slotRepository->findOneWithExperience($input->slotId);
        if (!$slot) {
            return $this->json([
                'error' => [
                    'code' => 'slot_not_found',
                    'message' => 'Slot not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $booking = $bookingService->createBooking($user, $slot, $input->seats);
            $entityManager->flush();
        } catch (\DomainException $exception) {
            return $this->json([
                'error' => [
                    'code' => 'booking_conflict',
                    'message' => $exception->getMessage(),
                ],
            ], Response::HTTP_CONFLICT);
        }

        $booking = $bookingRepository->findDetailedByIdForUser((int) $booking->getId(), $user) ?? $booking;

        return $this->json([
            'data' => $bookingApiPresenter->present($booking),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id<\d+>}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(
        int $id,
        BookingRepository $bookingRepository,
        BookingService $bookingService,
        EntityManagerInterface $entityManager,
        BookingApiPresenter $bookingApiPresenter,
    ): JsonResponse {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $booking = $bookingRepository->findDetailedByIdForUser($id, $user);
        if (!$booking) {
            return $this->json([
                'error' => [
                    'code' => 'booking_not_found',
                    'message' => 'Booking not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(BookingVoter::CANCEL, $booking);

        try {
            $booking = $bookingService->cancelBooking($booking);
            $entityManager->flush();
        } catch (\DomainException $exception) {
            return $this->json([
                'error' => [
                    'code' => 'booking_cannot_be_cancelled',
                    'message' => $exception->getMessage(),
                ],
            ], Response::HTTP_CONFLICT);
        }

        $booking = $bookingRepository->findDetailedByIdForUser((int) $booking->getId(), $user) ?? $booking;

        return $this->json([
            'data' => $bookingApiPresenter->present($booking),
        ]);
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
            $field = $violation->getPropertyPath();
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
}
