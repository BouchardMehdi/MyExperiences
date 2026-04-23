<?php

namespace App\Controller;

use App\Entity\Experience;
use App\Repository\ExperienceRepository;
use App\Repository\ReviewRepository;
use App\Repository\SlotRepository;
use App\Security\ExperienceVoter;
use App\Service\ReviewEligibilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/experiences')]
class ExperienceController extends AbstractController
{
    #[Route('', name: 'app_experience_index', methods: ['GET'])]
    public function index(Request $request, ExperienceRepository $experienceRepository): Response
    {
        $location = $request->query->getString('location') ?: null;
        $maxPrice = $request->query->get('maxPrice');
        $date = $request->query->getString('date');

        $dateFilter = null;
        if ($date) {
            try {
                $dateFilter = new \DateTimeImmutable($date);
            } catch (\Exception) {
                $this->addFlash('warning', 'Le filtre de date est invalide.');
            }
        }

        return $this->render('experience/index.html.twig', [
            'experiences' => $experienceRepository->findPublishedWithFilters(
                $location,
                is_numeric($maxPrice) ? (float) $maxPrice : null,
                $dateFilter
            ),
            'filters' => [
                'location' => $location,
                'maxPrice' => $maxPrice,
                'date' => $date,
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_experience_show', methods: ['GET'])]
    public function show(
        Experience $experience,
        SlotRepository $slotRepository,
        ReviewRepository $reviewRepository,
        ReviewEligibilityService $reviewEligibilityService,
    ): Response {
        $this->denyAccessUnlessGranted(ExperienceVoter::VIEW, $experience);

        $user = $this->getUser();

        return $this->render('experience/show.html.twig', [
            'experience' => $experience,
            'slots' => $slotRepository->findBookableForExperience($experience),
            'reviews' => $reviewRepository->findLatestForExperience($experience),
            'canReview' => $user && $reviewEligibilityService->canReview($user, $experience),
        ]);
    }
}
