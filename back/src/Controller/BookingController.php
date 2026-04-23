<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Slot;
use App\Entity\User;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Security\BookingVoter;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class BookingController extends AbstractController
{
    #[Route('/bookings', name: 'app_booking_index', methods: ['GET'])]
    public function index(BookingRepository $bookingRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookingRepository->findForUser($user),
        ]);
    }

    #[Route('/slots/{id}/book', name: 'app_booking_new', methods: ['GET', 'POST'])]
    public function new(Slot $slot, Request $request, BookingService $bookingService): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var User $user */
                $user = $this->getUser();
                $createdBooking = $bookingService->createBooking($user, $slot, $booking->getSeats());

                $this->addFlash('success', 'Réservation créée. Vous pouvez maintenant procéder au paiement mock.');

                return $this->redirectToRoute('app_payment_show', ['id' => $createdBooking->getId()]);
            } catch (\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->render('booking/new.html.twig', [
            'slot' => $slot,
            'bookingForm' => $form,
        ]);
    }

    #[Route('/bookings/{id}/cancel', name: 'app_booking_cancel', methods: ['POST'])]
    public function cancel(
        Booking $booking,
        Request $request,
        BookingService $bookingService,
    ): Response {
        $this->denyAccessUnlessGranted(BookingVoter::CANCEL, $booking);

        if (!$this->isCsrfTokenValid('cancel_booking_'.$booking->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $bookingService->cancelBooking($booking);
            $this->addFlash('success', 'La réservation a été annulée.');
        } catch (\DomainException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_booking_index');
    }
}
