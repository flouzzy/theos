<?php

namespace App\Controller;

use App\Repository\PaymentSettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/payment', name: 'payment_', priority: 3)]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(PaymentSettingRepository $paymentSettingRepository): Response
    {
        return $this->render('payment/index.html.twig', [
            'payment' => $paymentSettingRepository->findOneBy([]),
        ]);
    }
}
