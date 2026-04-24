<?php

namespace App\Tests\Controller;

use App\Controller\PaymentController;
use App\Entity\PaymentSetting;
use App\Repository\PaymentSettingRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PaymentControllerTest extends TestCase
{
    public function testIndexReturnsResponseWithPaymentSetting(): void
    {
        $paymentSettingRepository = $this->createMock(PaymentSettingRepository::class);
        $paymentSetting = new PaymentSetting();
        $paymentSettingRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn($paymentSetting);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('payment/index.html.twig', [
                'payment' => $paymentSetting,
            ])
            ->willReturn('rendered_template_content');

        $container = new Container();
        $container->set('twig', $twig);

        $controller = new PaymentController();
        $controller->setContainer($container);

        $response = $controller->index($paymentSettingRepository);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('rendered_template_content', $response->getContent());
    }

    public function testIndexReturnsResponseWithoutPaymentSetting(): void
    {
        $paymentSettingRepository = $this->createMock(PaymentSettingRepository::class);
        $paymentSettingRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn(null);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('payment/index.html.twig', [
                'payment' => null,
            ])
            ->willReturn('rendered_template_content_empty');

        $container = new Container();
        $container->set('twig', $twig);

        $controller = new PaymentController();
        $controller->setContainer($container);

        $response = $controller->index($paymentSettingRepository);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('rendered_template_content_empty', $response->getContent());
    }
}
