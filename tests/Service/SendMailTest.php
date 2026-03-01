<?php

namespace App\Tests\Service;

use App\Service\BrevoApi;
use App\Service\SendMail;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SendMailTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private LoggerInterface&MockObject $logger;
    private BrevoApi&MockObject $brevoApi;
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

    public function testSendWithTransportExceptionUsesAlternative(): void
    {
        $from = 'sender@example.com';
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $template = 'email/test.html.twig';
        $context = ['key' => 'value'];

        // Create a mock exception that implements TransportExceptionInterface
        $exception = new TransportException('Transport failed');

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->willThrowException($exception);

        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('error')
            ->with($exception->getDebug());

        // Expect mailer to be called with alternative transport header
        $this->mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (TemplatedEmail $email) use ($from, $to, $subject, $template, $context) {
                // Assert base email properties
                $fromAddresses = $email->getFrom();
                $this->assertCount(1, $fromAddresses);
                $this->assertEquals($from, $fromAddresses[0]->getAddress());

                $toAddresses = $email->getTo();
                $this->assertCount(1, $toAddresses);
                $this->assertEquals($to, $toAddresses[0]->getAddress());

                $this->assertEquals($subject, $email->getSubject());
                $this->assertEquals($template, $email->getHtmlTemplate());
                $this->assertEquals($context, $email->getContext());

                // Assert X-Transport header is present and set to 'alternative'
                $headers = $email->getHeaders();
                $this->assertTrue($headers->has('X-Transport'));

                $header = $headers->get('X-Transport');
                $this->assertEquals('alternative', $header->getBodyAsString());
            });

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }

    public function testSendSuccessful(): void
    {
        $from = new Address('sender@example.com', 'Sender Name');
        $to = [new Address('recipient1@example.com', 'Recipient 1'), 'recipient2@example.com'];
        $subject = 'Test Subject';
        $template = 'email/test.html.twig';
        $context = ['key' => 'value'];

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with(
                [
                    ['email' => 'recipient1@example.com', 'name' => 'Recipient 1'],
                    ['email' => 'recipient2@example.com']
                ],
                $context
            );

        $this->logger->expects($this->never())->method('error');
        $this->mailer->expects($this->never())->method('send');

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }
}
