<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SubscriptionServiceTest extends TestCase
{
    private StripeClient&MockObject $stripeClient;
    private EntityManagerInterface&MockObject $entityManager;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private EntityRepository&MockObject $userRepository;

    protected function setUp(): void
    {
        $this->stripeClient = $this->createMock(StripeClient::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->userRepository = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);
    }

    private function createServiceWithMockedConstructEvent(?\Stripe\Event $eventToReturn, ?\Throwable $exceptionToThrow = null): SubscriptionService
    {
        $service = $this->getMockBuilder(SubscriptionService::class)
            ->setConstructorArgs([$this->stripeClient, $this->entityManager, $this->urlGenerator])
            ->onlyMethods(['constructEvent'])
            ->getMock();

        if ($exceptionToThrow) {
            $service->expects($this->once())
                ->method('constructEvent')
                ->willThrowException($exceptionToThrow);
        } else {
            $service->expects($this->once())
                ->method('constructEvent')
                ->willReturn($eventToReturn);
        }

        return $service;
    }

    public function testCreateCheckoutSessionWithExistingCustomer(): void
    {
        $user = new User();
        $user->setStripeCustomerId('cus_123');

        $sessionMock = (object) ['url' => 'https://checkout.stripe.com/c/pay/cs_test_123'];

        $sessionsMock = $this->createMock(\Stripe\Service\Checkout\SessionService::class);
        $sessionsMock->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $options) {
                return $options['customer'] === 'cus_123'
                    && $options['mode'] === 'subscription'
                    && $options['line_items'][0]['price'] === 'price_123';
            }))
            ->willReturn($sessionMock);

        $checkoutMock = (object) ['sessions' => $sessionsMock];
        $this->stripeClient->checkout = $checkoutMock;

        $this->urlGenerator->method('generate')->willReturn('http://localhost/success');

        $service = new SubscriptionService($this->stripeClient, $this->entityManager, $this->urlGenerator);
        $url = $service->createCheckoutSession($user, 'price_123');

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_123', $url);
    }

    public function testCreateCheckoutSessionWithNewCustomer(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        // Stripe customer ID is null initially

        $customerMock = (object) ['id' => 'cus_456'];
        $sessionMock = (object) ['url' => 'https://checkout.stripe.com/c/pay/cs_test_456'];

        $customersMock = $this->createMock(\Stripe\Service\CustomerService::class);
        $customersMock->expects($this->once())
            ->method('create')
            ->with([
                'email' => 'test@example.com',
                'name' => $user->getFullname(),
            ])
            ->willReturn($customerMock);

        $this->stripeClient->customers = $customersMock;

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $sessionsMock = $this->createMock(\Stripe\Service\Checkout\SessionService::class);
        $sessionsMock->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $options) {
                return $options['customer'] === 'cus_456';
            }))
            ->willReturn($sessionMock);

        $checkoutMock = (object) ['sessions' => $sessionsMock];
        $this->stripeClient->checkout = $checkoutMock;

        $this->urlGenerator->method('generate')->willReturn('http://localhost/success');

        $service = new SubscriptionService($this->stripeClient, $this->entityManager, $this->urlGenerator);
        $url = $service->createCheckoutSession($user, 'price_123');

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_456', $url);
        $this->assertSame('cus_456', $user->getStripeCustomerId());
    }

    public function testHandleWebhookInvalidPayload(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid payload');

        $service = $this->createServiceWithMockedConstructEvent(null, new \UnexpectedValueException('Invalid'));
        $service->handleWebhook('payload', 'sig', 'secret');
    }

    public function testHandleWebhookInvalidSignature(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid signature');

        $service = $this->createServiceWithMockedConstructEvent(null, \Stripe\Exception\SignatureVerificationException::factory('Invalid', 'sig', 'payload'));
        $service->handleWebhook('payload', 'sig', 'secret');
    }

    public function testHandleWebhookInvoicePaid(): void
    {
        $invoice = new \Stripe\Invoice();
        $invoice->customer = 'cus_123';
        $invoice->payment_intent = 'pi_123';
        $invoice->amount_paid = 1000;
        $invoice->currency = 'eur';

        $event = new \Stripe\Event();
        $event->type = 'invoice.paid';
        $event->data = (object) ['object' => $invoice];

        $user = new User();
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeCustomerId' => 'cus_123'])
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Transaction::class));
        $this->entityManager->expects($this->once())
            ->method('flush');

        $service = $this->createServiceWithMockedConstructEvent($event);
        $service->handleWebhook('payload', 'sig', 'secret');
    }

    public function testHandleWebhookCustomerSubscriptionCreated(): void
    {
        $subscription = \Stripe\Subscription::constructFrom([
            'id' => 'sub_123',
            'customer' => 'cus_123',
            'status' => 'active',
            'items' => [
                'data' => [
                    [
                        'price' => [
                            'id' => 'price_monthly',
                        ],
                    ],
                ],
            ],
        ]);

        $event = new \Stripe\Event();
        $event->type = 'customer.subscription.created';
        $event->data = (object) ['object' => $subscription];

        $user = new User();
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeCustomerId' => 'cus_123'])
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $_ENV['STRIPE_PRICE_MONTHLY'] = 'price_monthly';

        $service = $this->createServiceWithMockedConstructEvent($event);
        $service->handleWebhook('payload', 'sig', 'secret');

        $this->assertSame('sub_123', $user->getSubscriptionId());
        $this->assertSame('active', $user->getSubscriptionStatus());
        $this->assertSame('pro_monthly', $user->getSubscriptionPlan());
    }

    public function testHandleWebhookCustomerSubscriptionDeleted(): void
    {
        $subscription = new \Stripe\Subscription();
        $subscription->customer = 'cus_123';

        $event = new \Stripe\Event();
        $event->type = 'customer.subscription.deleted';
        $event->data = (object) ['object' => $subscription];

        $user = new User();
        $user->setSubscriptionStatus('active');

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeCustomerId' => 'cus_123'])
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $service = $this->createServiceWithMockedConstructEvent($event);
        $service->handleWebhook('payload', 'sig', 'secret');

        $this->assertSame('canceled', $user->getSubscriptionStatus());
    }
}
