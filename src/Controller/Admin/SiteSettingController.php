<?php

namespace App\Controller\Admin;

use App\Entity\SiteSetting;
use App\Form\SiteSettingType;
use App\Repository\SiteSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/settings', name: 'admin_setting_')]
class SiteSettingController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(SiteSettingRepository $siteSettingRepository, EntityManagerInterface $em, string $appName): Response
    {
        // Créer le paramètre par défaut s'il n'existe pas encore
        $coachPrompt = $siteSettingRepository->findOneBy(['name' => 'COACH_PROMPT']);
        
        if (!$coachPrompt) {
            $coachPrompt = new SiteSetting();
            $coachPrompt->setName('COACH_PROMPT');
            $coachPrompt->setDescription('Prompt système utilisé par le Coach IA');
            $coachPrompt->setValue(sprintf("Tu es un coach pédagogique francophone travaillant pour %s, une école de théologie. Ton rôle est d'encourager l'étudiant, de l'aider dans sa réflexion spirituelle et académique de façon concise et conviviale. Ne donne pas de réponses longues (> 100 mots par message) sauf si c'est très technique ou théologique. Utilise des emojis de temps en temps.", $appName));
            $em->persist($coachPrompt);
            $em->flush();
        }

        return $this->render('admin/site_setting/index.html.twig', [
            'settings' => $siteSettingRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SiteSetting $siteSetting, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SiteSettingType::class, $siteSetting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Paramètre mis à jour avec succès');

            return $this->redirectToRoute('admin_setting_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/site_setting/edit.html.twig', [
            'site_setting' => $siteSetting,
            'form' => $form->createView(),
        ]);
    }
}
