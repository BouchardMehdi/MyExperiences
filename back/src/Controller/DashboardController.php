<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(BookingRepository $bookingRepository, ReviewRepository $reviewRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $bookings = $bookingRepository->findForUser($user);

        $paidCount = count(array_filter($bookings, static fn ($booking) => BookingStatus::PAID === $booking->getStatus()));
        $pendingCount = count(array_filter($bookings, static fn ($booking) => BookingStatus::PENDING === $booking->getStatus()));

        return $this->render('dashboard/index.html.twig', [
            'bookings' => array_slice($bookings, 0, 5),
            'stats' => [
                'totalBookings' => count($bookings),
                'paidBookings' => $paidCount,
                'pendingBookings' => $pendingCount,
                'reviews' => $reviewRepository->count(['user' => $user]),
            ],
        ]);
    }
}
