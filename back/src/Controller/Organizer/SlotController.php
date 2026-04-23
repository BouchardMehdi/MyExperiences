<?php

namespace App\Controller\Organizer;

use App\Entity\Experience;
use App\Entity\Slot;
use App\Form\SlotType;
use App\Security\ExperienceVoter;
use App\Security\SlotVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organizer')]
#[IsGranted('ROLE_ORGANIZER')]
class SlotController extends AbstractController
{
    #[Route('/experiences/{id}/slots', name: 'app_organizer_slot_index', methods: ['GET'])]
    public function index(Experience $experience): Response
    {
        $this->denyAccessUnlessGranted(ExperienceVoter::MANAGE, $experience);

        return $this->render('organizer/slot/index.html.twig', [
            'experience' => $experience,
        ]);
    }

    #[Route('/experiences/{id}/slots/new', name: 'app_organizer_slot_new', methods: ['GET', 'POST'])]
    public function new(Experience $experience, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(ExperienceVoter::MANAGE, $experience);

        $slot = (new Slot())->setExperience($experience);
        $form = $this->createForm(SlotType::class, $slot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($slot);
            $entityManager->flush();

            $this->addFlash('success', 'Créneau ajouté.');

            return $this->redirectToRoute('app_organizer_slot_index', ['id' => $experience->getId()]);
        }

        return $this->render('organizer/slot/form.html.twig', [
            'form' => $form,
            'experience' => $experience,
            'slot' => $slot,
            'pageTitle' => 'Créer un créneau',
        ]);
    }

    #[Route('/slots/{id}/edit', name: 'app_organizer_slot_edit', methods: ['GET', 'POST'])]
    public function edit(Slot $slot, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SlotVoter::MANAGE, $slot);

        $form = $this->createForm(SlotType::class, $slot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Créneau mis à jour.');

            return $this->redirectToRoute('app_organizer_slot_index', ['id' => $slot->getExperience()?->getId()]);
        }

        return $this->render('organizer/slot/form.html.twig', [
            'form' => $form,
            'experience' => $slot->getExperience(),
            'slot' => $slot,
            'pageTitle' => 'Modifier un créneau',
        ]);
    }

    #[Route('/slots/{id}/delete', name: 'app_organizer_slot_delete', methods: ['POST'])]
    public function delete(Slot $slot, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SlotVoter::MANAGE, $slot);

        if (!$this->isCsrfTokenValid('delete_slot_'.$slot->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($slot->getBookings()->count() > 0) {
            $this->addFlash('warning', 'Impossible de supprimer un créneau ayant déjà des réservations.');

            return $this->redirectToRoute('app_organizer_slot_index', ['id' => $slot->getExperience()?->getId()]);
        }

        $experienceId = $slot->getExperience()?->getId();
        $entityManager->remove($slot);
        $entityManager->flush();

        $this->addFlash('success', 'Créneau supprimé.');

        return $this->redirectToRoute('app_organizer_slot_index', ['id' => $experienceId]);
    }
}
