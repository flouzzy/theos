<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BrevoApi;
use App\Service\SendMail;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SendMailTest extends TestCase
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private BrevoApi $brevoApi;
    private SendMail $sendMail;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brevoApi = $this->createMock(BrevoApi::class);

        $this->sendMail = new SendMail(
            $this->mailer,
            $this->logger,
            $this->brevoApi
        );
    }

    public function testSendSuccess(): void
    {
        $from = new Address('from@example.com', 'Sender Name');
        $to = 'to@example.com';
        $subject = 'Test Subject';
        $template = 'test/template.html.twig';
        $context = ['key' => 'value'];

        $expectedToList = [
            ['email' => 'to@example.com']
        ];

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with($expectedToList, $context);

        $this->logger->expects($this->never())
            ->method('error');

        $this->mailer->expects($this->never())
            ->method('send');

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }

    public function testSendSuccessWithAddressObject(): void
    {
        $from = new Address('from@example.com', 'Sender Name');
        $to = [new Address('to1@example.com', 'Recipient One')];
        $subject = 'Test Subject';
        $template = 'test/template.html.twig';
        $context = ['key' => 'value'];

        $expectedToList = [
            ['email' => 'to1@example.com', 'name' => 'Recipient One']
        ];

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with($expectedToList, $context);

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }

    public function testSendWithTransportExceptionFallback(): void
    {
        $from = 'from@example.com';
        $to = ['to1@example.com', 'to2@example.com'];
        $subject = 'Error Fallback Subject';
        $template = 'test/error_template.html.twig';
        $context = ['error' => 'true'];

        $expectedToList = [
            ['email' => 'to1@example.com'],
            ['email' => 'to2@example.com']
        ];

        $exception = new TransportException('Brevo API connection failed');

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with($expectedToList, $context)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exception->getDebug());

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($from, $to, $subject, $template, $context) {
                $hasAlternativeTransport = false;
                if ($email->getHeaders()->has('X-Transport')) {
                    $hasAlternativeTransport = $email->getHeaders()->get('X-Transport')->getBody() === 'alternative';
                }

                return $email->getFrom()[0]->getAddress() === $from
                    && $email->getTo()[0]->getAddress() === $to[0]
                    && $email->getTo()[1]->getAddress() === $to[1]
                    && $email->getSubject() === $subject
                    && $email->getHtmlTemplate() === $template
                    && $email->getContext() === $context
                    && $hasAlternativeTransport;
            }));

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }
}
