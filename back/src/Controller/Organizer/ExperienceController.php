<?php

namespace App\Controller\Organizer;

use App\Entity\Experience;
use App\Entity\User;
use App\Form\ExperienceType;
use App\Repository\ExperienceRepository;
use App\Security\ExperienceVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organizer/experiences')]
#[IsGranted('ROLE_ORGANIZER')]
class ExperienceController extends AbstractController
{
    #[Route('', name: 'app_organizer_experience_index', methods: ['GET'])]
    public function index(ExperienceRepository $experienceRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('organizer/experience/index.html.twig', [
            'experiences' => $experienceRepository->findForOrganizer($user),
        ]);
    }

    #[Route('/new', name: 'app_organizer_experience_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $experience = (new Experience())->setOrganizer($user);
        $form = $this->createForm(ExperienceType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($experience);
            $entityManager->flush();

            $this->addFlash('success', 'Expérience créée.');

            return $this->redirectToRoute('app_organizer_experience_index');
        }

        return $this->render('organizer/experience/form.html.twig', [
            'form' => $form,
            'experience' => $experience,
            'pageTitle' => 'Créer une expérience',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_organizer_experience_edit', methods: ['GET', 'POST'])]
    public function edit(Experience $experience, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(ExperienceVoter::MANAGE, $experience);

        $form = $this->createForm(ExperienceType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Expérience mise à jour.');

            return $this->redirectToRoute('app_organizer_experience_index');
        }

        return $this->render('organizer/experience/form.html.twig', [
            'form' => $form,
            'experience' => $experience,
            'pageTitle' => 'Modifier une expérience',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_organizer_experience_delete', methods: ['POST'])]
    public function delete(Experience $experience, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(ExperienceVoter::MANAGE, $experience);

        if (!$this->isCsrfTokenValid('delete_experience_'.$experience->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($experience);
        $entityManager->flush();

        $this->addFlash('success', 'Expérience supprimée.');

        return $this->redirectToRoute('app_organizer_experience_index');
    }
}
