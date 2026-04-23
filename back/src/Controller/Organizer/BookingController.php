<?php

namespace App\Controller\Organizer;

use App\Entity\User;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organizer/bookings')]
#[IsGranted('ROLE_ORGANIZER')]
class BookingController extends AbstractController
{
    #[Route('', name: 'app_organizer_booking_index', methods: ['GET'])]
    public function index(BookingRepository $bookingRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('organizer/booking/index.html.twig', [
            'bookings' => $bookingRepository->findForOrganizer($user),
        ]);
    }
}
