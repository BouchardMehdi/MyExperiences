<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Security\BookingVoter;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    #[Route('/payments/{id}', name: 'app_payment_show', methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        $this->denyAccessUnlessGranted(BookingVoter::PAY, $booking);

        return $this->render('payment/show.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/payments/{id}/success', name: 'app_payment_success', methods: ['POST'])]
    public function success(Booking $booking, Request $request, PaymentService $paymentService): Response
    {
        $this->denyAccessUnlessGranted(BookingVoter::PAY, $booking);

        if (!$this->isCsrfTokenValid('payment_success_'.$booking->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $paymentService->simulateSuccess($booking);
            $this->addFlash('success', 'Paiement mock validé avec succès.');
        } catch (\DomainException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_booking_index');
    }

    #[Route('/payments/{id}/failure', name: 'app_payment_failure', methods: ['POST'])]
    public function failure(Booking $booking, Request $request, PaymentService $paymentService): Response
    {
        $this->denyAccessUnlessGranted(BookingVoter::PAY, $booking);

        if (!$this->isCsrfTokenValid('payment_failure_'.$booking->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $paymentService->simulateFailure($booking);
            $this->addFlash('warning', 'Paiement mock échoué. La réservation a été annulée et les places ont été libérées.');
        } catch (\DomainException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_booking_index');
    }
}
