<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SubscriptionServiceTest extends TestCase
{
    /** @var StripeClient&MockObject */
    private StripeClient $stripeClient;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var UrlGeneratorInterface&MockObject */
    private UrlGeneratorInterface $urlGenerator;

    private SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        $this->stripeClient = $this->createMock(StripeClient::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->subscriptionService = new SubscriptionService(
            $this->stripeClient,
            $this->entityManager,
            $this->urlGenerator
        );
    }

    public function testCreateCheckoutSessionWithExistingStripeCustomer(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFullname('Test User');
        $user->setStripeCustomerId('cus_12345');

        $priceId = 'price_123';

        // Set up the mock for urlGenerator
        $this->urlGenerator->method('generate')
            ->willReturnMap([
                ['app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL, 'https://example.com/success'],
                ['app_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL, 'https://example.com/cancel'],
            ]);

        // Mock Stripe checkout sessions service
        $checkoutSessionsServiceMock = $this->createMock(\Stripe\Service\Checkout\SessionService::class);

        $this->stripeClient->method('__get')
            ->willReturnMap([
                ['checkout', (object) ['sessions' => $checkoutSessionsServiceMock]],
            ]);

        // Mock the create method on checkout sessions service
        $sessionMock = new \Stripe\Checkout\Session('cs_test_123');
        $sessionMock->url = 'https://checkout.stripe.com/c/pay/cs_test_123';

        $checkoutSessionsServiceMock->expects($this->once())
            ->method('create')
            ->with([
                'customer' => 'cus_12345',
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => 'price_123',
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => 'https://example.com/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://example.com/cancel',
            ])
            ->willReturn($sessionMock);

        // We don't expect entityManager to be called because customer exists
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $resultUrl = $this->subscriptionService->createCheckoutSession($user, $priceId);

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_123', $resultUrl);
    }

    public function testCreateCheckoutSessionWithoutExistingStripeCustomer(): void
    {
        $user = new User();
        $user->setEmail('newuser@example.com');
        $user->setFullname('New User');

        $priceId = 'price_456';

        // Mock urlGenerator
        $this->urlGenerator->method('generate')
            ->willReturnMap([
                ['app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL, 'https://example.com/success'],
                ['app_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL, 'https://example.com/cancel'],
            ]);

        // Mock Stripe customers service
        $customerServiceMock = $this->createMock(\Stripe\Service\CustomerService::class);
        $customerMock = new \Stripe\Customer('cus_new_456');

        $customerServiceMock->expects($this->once())
            ->method('create')
            ->with([
                'email' => 'newuser@example.com',
                'name' => 'New User',
            ])
            ->willReturn($customerMock);

        // We expect entityManager to be called to save the new customer ID
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Mock Stripe checkout sessions service
        $checkoutSessionsServiceMock = $this->createMock(\Stripe\Service\Checkout\SessionService::class);

        $this->stripeClient->method('__get')
            ->willReturnMap([
                ['customers', $customerServiceMock],
                ['checkout', (object) ['sessions' => $checkoutSessionsServiceMock]],
            ]);

        // Mock the create method on checkout sessions service
        $sessionMock = new \Stripe\Checkout\Session('cs_test_456');
        $sessionMock->url = 'https://checkout.stripe.com/c/pay/cs_test_456';

        $checkoutSessionsServiceMock->expects($this->once())
            ->method('create')
            ->with([
                'customer' => 'cus_new_456',
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => 'price_456',
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => 'https://example.com/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://example.com/cancel',
            ])
            ->willReturn($sessionMock);

        $resultUrl = $this->subscriptionService->createCheckoutSession($user, $priceId);

        $this->assertSame('cus_new_456', $user->getStripeCustomerId());
        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_456', $resultUrl);
    }
}
