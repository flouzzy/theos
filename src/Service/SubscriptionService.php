<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SubscriptionService
{
    public function __construct(
        private StripeClient $stripeClient,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    /**
     * Crée une session de checkout Stripe pour un utilisateur.
     */
    public function createCheckoutSession(User $user, string $priceId): string
    {
        // Créer un client Stripe si nécessaire
        if (!$user->getStripeCustomerId()) {
            $customer = $this->stripeClient->customers->create([
                'email' => (string) $user->getEmail(),
                'name' => (string) $user->getFullname(),
            ]);
            $user->setStripeCustomerId($customer->id);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $session = $this->stripeClient->checkout->sessions->create([
            'customer' => (string) $user->getStripeCustomerId(),
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $this->urlGenerator->generate('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->urlGenerator->generate('app_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return (string) $session->url;
    }

    /**
     * Gère les événements webhook de Stripe.
     */
    public function handleWebhook(string $payload, string $sigHeader, string $webhookSecret): void
    {
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            throw new \Exception('Invalid payload');
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            throw new \Exception('Invalid signature');
        }

        switch ($event->type) {
            case 'invoice.paid':
                $invoice = $event->data->object;
                if ($invoice instanceof \Stripe\Invoice) {
                    $this->logTransaction($invoice);
                }
                break;
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                if ($subscription instanceof \Stripe\Subscription) {
                    $this->updateUserSubscription($subscription);
                }
                break;
            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                if ($subscription instanceof \Stripe\Subscription) {
                    $this->cancelUserSubscription($subscription);
                }
                break;
        }
    }

    private function updateUserSubscription(\Stripe\Subscription $subscription): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['stripeCustomerId' => (string) $subscription->customer]);
        if ($user) {
            $user->setSubscriptionId((string) $subscription->id);
            $user->setSubscriptionStatus((string) $subscription->status);
            
            // On peut mapper le price ID à un plan interne
            $priceId = (string) $subscription->items->data[0]->price->id;
            $user->setSubscriptionPlan($this->mapPriceToPlan($priceId));

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    private function cancelUserSubscription(\Stripe\Subscription $subscription): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['stripeCustomerId' => (string) $subscription->customer]);
        if ($user) {
            $user->setSubscriptionStatus('canceled');
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    private function mapPriceToPlan(string $priceId): string
    {
        // À configurer via env ou DB pour plus de flexibilité
        return match($priceId) {
            $_ENV['STRIPE_PRICE_MONTHLY'] ?? '' => 'pro_monthly',
            $_ENV['STRIPE_PRICE_YEARLY'] ?? '' => 'pro_yearly',
            default => 'custom'
        };
    }

    private function logTransaction(\Stripe\Invoice $invoice): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['stripeCustomerId' => (string) $invoice->customer]);
        if ($user) {
            $transaction = new Transaction();
            $transaction->setUser($user);
            $transaction->setStripePaymentId((string) ($invoice->payment_intent ?? $invoice->id));
            $transaction->setAmount((int) $invoice->amount_paid);
            $transaction->setCurrency((string) $invoice->currency);
            $transaction->setStatus('succeeded');

            $this->entityManager->persist($transaction);
            $this->entityManager->flush();
        }
    }
}
