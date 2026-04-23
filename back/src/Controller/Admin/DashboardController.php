<?php

namespace App\Controller\Admin;

use App\Repository\BookingRepository;
use App\Repository\ExperienceRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        ExperienceRepository $experienceRepository,
        BookingRepository $bookingRepository,
        ReviewRepository $reviewRepository,
    ): Response {
        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'users' => $userRepository->count([]),
                'experiences' => $experienceRepository->count([]),
                'bookings' => $bookingRepository->count([]),
                'reviews' => $reviewRepository->count([]),
            ],
        ]);
    }
}
