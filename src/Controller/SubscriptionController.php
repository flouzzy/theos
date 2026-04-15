<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SubscriptionController extends AbstractController
{
    #[Route('/subscription/checkout/{priceId}', name: 'app_subscription_checkout')]
    public function checkout(string $priceId, SubscriptionService $subscriptionService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $checkoutUrl = $subscriptionService->createCheckoutSession($user, $priceId);

        return $this->redirect($checkoutUrl);
    }

    #[Route('/subscription/success', name: 'app_subscription_success')]
    public function success(): Response
    {
        $this->addFlash('success', 'Votre abonnement a été activé avec succès !');
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/subscription/cancel', name: 'app_subscription_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le processus d\'abonnement a été annulé.');
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/webhook/stripe', name: 'app_stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        SubscriptionService $subscriptionService,
        #[Autowire(env: 'STRIPE_WEBHOOK_SECRET')]
        string $webhookSecret
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $subscriptionService->handleWebhook($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            return new Response('Webhook error', Response::HTTP_BAD_REQUEST);
        }

        return new Response('Webhook handled', Response::HTTP_OK);
    }
}
