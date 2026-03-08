<?php

namespace App\Controller\Admin;

use App\Entity\Setting;
use App\Form\SettingType;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/setting', name: 'admin_setting_')]
#[IsGranted('ROLE_ADMIN')]
class SettingController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, SettingRepository $settingRepository, EntityManagerInterface $entityManager): Response
    {
        $setting = $settingRepository->getSettings();
        $form = $this->createForm(SettingType::class, $setting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Paramètres mis à jour avec succès.');

            return $this->redirectToRoute('admin_setting_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/setting/index.html.twig', [
            'setting' => $setting,
            'form' => $form,
        ]);
    }
}
