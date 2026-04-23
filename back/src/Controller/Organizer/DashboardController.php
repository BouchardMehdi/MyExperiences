<?php

namespace App\Controller\Organizer;

use App\Entity\User;
use App\Enum\ExperienceStatus;
use App\Repository\BookingRepository;
use App\Repository\ExperienceRepository;
use App\Repository\SlotRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organizer')]
#[IsGranted('ROLE_ORGANIZER')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_organizer_dashboard', methods: ['GET'])]
    public function index(
        ExperienceRepository $experienceRepository,
        SlotRepository $slotRepository,
        BookingRepository $bookingRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $experiences = $experienceRepository->findForOrganizer($user);
        $slots = $slotRepository->findForOrganizer($user);
        $bookings = $bookingRepository->findForOrganizer($user);

        return $this->render('organizer/dashboard/index.html.twig', [
            'stats' => [
                'experiences' => count($experiences),
                'published' => count(array_filter($experiences, static fn ($experience) => ExperienceStatus::PUBLISHED === $experience->getStatus())),
                'slots' => count($slots),
                'bookings' => count($bookings),
            ],
            'recentBookings' => array_slice($bookings, 0, 8),
        ]);
    }
}
