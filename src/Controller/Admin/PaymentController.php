<?php

namespace App\Controller\Admin;

use App\Entity\PaymentSetting;
use App\Entity\User;
use App\Form\PaymentSettingType;
use App\Repository\PaymentSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/payment', name: 'admin_payment_')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PaymentSettingRepository $paymentSettingRepository): Response
    {
        return $this->render('admin/payment/index.html.twig', [
            'payment_settings' => $paymentSettingRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $paymentSetting = new PaymentSetting();
        $form = $this->createForm(PaymentSettingType::class, $paymentSetting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($paymentSetting);
            $entityManager->flush();

            return $this->redirectToRoute('admin_payment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/payment/new.html.twig', [
            'payment_setting' => $paymentSetting,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PaymentSetting $paymentSetting): Response
    {
        return $this->render('admin/payment/show.html.twig', [
            'payment_setting' => $paymentSetting,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PaymentSetting $paymentSetting, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaymentSettingType::class, $paymentSetting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_payment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/payment/edit.html.twig', [
            'payment_setting' => $paymentSetting,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, PaymentSetting $paymentSetting, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('delete' . $paymentSetting->getId(), $token)) {
            $entityManager->remove($paymentSetting);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_payment_index', [], Response::HTTP_SEE_OTHER);
    }

}
