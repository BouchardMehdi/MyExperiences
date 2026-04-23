<?php

namespace App\Controller\Admin;

use App\Entity\Experience;
use App\Enum\ExperienceStatus;
use App\Repository\ExperienceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/experiences')]
#[IsGranted('ROLE_ADMIN')]
class ExperienceController extends AbstractController
{
    #[Route('', name: 'app_admin_experience_index', methods: ['GET'])]
    public function index(ExperienceRepository $experienceRepository): Response
    {
        return $this->render('admin/experience/index.html.twig', [
            'experiences' => $experienceRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_admin_experience_status', methods: ['POST'])]
    public function updateStatus(
        Experience $experience,
        string $status,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_experience_status_'.$experience->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $enum = ExperienceStatus::tryFrom(strtoupper($status));
        if (!$enum) {
            throw $this->createNotFoundException('Statut inconnu.');
        }

        $experience->setStatus($enum);
        $entityManager->flush();

        $this->addFlash('success', 'Statut de l’expérience mis à jour.');

        return $this->redirectToRoute('app_admin_experience_index');
    }
}
