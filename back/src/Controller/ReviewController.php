<?php

namespace App\Controller;

use App\Entity\Experience;
use App\Entity\Review;
use App\Entity\User;
use App\Form\ReviewType;
use App\Service\ReviewEligibilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ReviewController extends AbstractController
{
    #[Route('/experiences/{id}/review', name: 'app_review_new', methods: ['GET', 'POST'])]
    public function new(
        Experience $experience,
        Request $request,
        EntityManagerInterface $entityManager,
        ReviewEligibilityService $reviewEligibilityService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$reviewEligibilityService->canReview($user, $experience)) {
            $this->addFlash('warning', 'Vous ne pouvez pas encore laisser d’avis pour cette expérience.');

            return $this->redirectToRoute('app_experience_show', ['id' => $experience->getId()]);
        }

        $review = (new Review())
            ->setUser($user)
            ->setExperience($experience);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Merci pour votre avis.');

            return $this->redirectToRoute('app_experience_show', ['id' => $experience->getId()]);
        }

        return $this->render('review/new.html.twig', [
            'experience' => $experience,
            'reviewForm' => $form,
        ]);
    }
}
