<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reviews')]
#[IsGranted('ROLE_ADMIN')]
class ReviewController extends AbstractController
{
    #[Route('', name: 'app_admin_review_index', methods: ['GET'])]
    public function index(ReviewRepository $reviewRepository): Response
    {
        return $this->render('admin/review/index.html.twig', [
            'reviews' => $reviewRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_review_delete', methods: ['POST'])]
    public function delete(Review $review, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_review_'.$review->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($review);
        $entityManager->flush();

        $this->addFlash('success', 'Avis supprimé.');

        return $this->redirectToRoute('app_admin_review_index');
    }
}
