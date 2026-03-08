<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BrevoApi;
use App\Service\SendMail;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Twig\Environment;

class SendMailTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private LoggerInterface&MockObject $logger;
    private BrevoApi&MockObject $brevoApi;
    private ParameterBagInterface&MockObject $parameterBag;
    private Environment&MockObject $twig;
    private SendMail $sendMail;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brevoApi = $this->createMock(BrevoApi::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->twig = $this->createMock(Environment::class);

        // Assert 'prod' environment to bypass dev/test override and test Brevo behaviour
        $this->parameterBag->method('get')->willReturnMap([
            ['brevo_api_key', 'valid-api-key'],
            ['kernel.environment', 'prod'],
        ]);
        
        $this->twig->method('render')->willReturn('<h1>Mocked HTML</h1>');

        $this->sendMail = new SendMail(
            $this->mailer,
            $this->logger,
            $this->brevoApi,
            $this->parameterBag,
            $this->twig
        );
    }

    public function testSendSingleStringEmail(): void
    {
        $from = 'sender@example.com';
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $template = 'test/template.html.twig';
        $context = ['key' => 'value'];

        $expectedToList = [['email' => $to]];

        $expectedContext = $context;
        $expectedContext['subject'] = $subject;
        $expectedContext['html_content'] = '<h1>Mocked HTML</h1>';

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with($expectedToList, $expectedContext);

        $this->logger->expects($this->never())->method('error');
        $this->mailer->expects($this->never())->method('send');

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }

    public function testSendArrayStringEmails(): void
    {
        $from = 'sender@example.com';
        $to = ['recipient1@example.com', 'recipient2@example.com'];
        $subject = 'Test Subject';
        $template = 'test/template.html.twig';
        $context = ['key' => 'value'];

        $expectedToList = [
            ['email' => 'recipient1@example.com'],
            ['email' => 'recipient2@example.com'],
        ];

        $expectedContext = $context;
        $expectedContext['subject'] = $subject;
        $expectedContext['html_content'] = '<h1>Mocked HTML</h1>';

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with($expectedToList, $expectedContext);

        $this->logger->expects($this->never())->method('error');
        $this->mailer->expects($this->never())->method('send');

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }

    public function testSendAddressObjects(): void
    {
        $from = 'sender@example.com';
        $to = [
            new \Symfony\Component\Mime\Address('recipient1@example.com', 'Recipient 1'),
            new \Symfony\Component\Mime\Address('recipient2@example.com'),
        ];
        $subject = 'Test Subject';
        $template = 'test/template.html.twig';
        $context = ['key' => 'value'];

        $expectedToList = [
            ['email' => 'recipient1@example.com', 'name' => 'Recipient 1'],
            ['email' => 'recipient2@example.com', 'name' => ''],
        ];

        $expectedContext = $context;
        $expectedContext['subject'] = $subject;
        $expectedContext['html_content'] = '<h1>Mocked HTML</h1>';

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with($expectedToList, $expectedContext);

        $this->logger->expects($this->never())->method('error');
        $this->mailer->expects($this->never())->method('send');

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }

    public function testSendThrowsTransportException(): void
    {
        $from = 'sender@example.com';
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $template = 'test/template.html.twig';
        $context = ['key' => 'value'];

        $expectedToList = [['email' => $to]]; // Added this line

        $expectedContext = $context;
        $expectedContext['subject'] = $subject;
        $expectedContext['html_content'] = '<h1>Mocked HTML</h1>';

        $exception = new \Symfony\Component\Mailer\Exception\TransportException('Transport error');
        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with($expectedToList, $expectedContext)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exception->getMessage());

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (\Symfony\Bridge\Twig\Mime\TemplatedEmail $email) use ($from, $to, $subject, $template, $context) {
                return $email->getFrom()[0]->getAddress() === $from
                    && $email->getTo()[0]->getAddress() === $to
                    && $email->getSubject() === $subject
                    && $email->getHtmlTemplate() === $template
                    && $email->getContext() === $context;
            }));

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }
}
