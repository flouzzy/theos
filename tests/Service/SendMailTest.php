<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BrevoApi;
use App\Service\SendMail;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

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

    public function testSendSuccessfullyCallsBrevoApi(): void
    {
        $from = 'sender@example.com';
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $template = 'test/template.html.twig';
        $context = ['key' => 'value'];

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with([['email' => $to]], $context);

        $this->mailer->expects($this->never())
            ->method('send');

        $this->logger->expects($this->never())
            ->method('error');

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }

    public function testSendCatchesTransportExceptionAndUsesAlternativeTransport(): void
    {
        $from = 'sender@example.com';
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $template = 'test/template.html.twig';
        $context = ['key' => 'value'];

        $exceptionMessage = 'Transport failed';
        $exception = $this->createMock(TransportExceptionInterface::class);
        $exception->method('getDebug')->willReturn($exceptionMessage);

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exceptionMessage);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($from, $to, $subject, $template, $context) {
                // Verify basic email properties
                $this->assertEquals([new Address($from)], $email->getFrom());
                $this->assertEquals([new Address($to)], $email->getTo());
                $this->assertEquals($subject, $email->getSubject());
                $this->assertEquals($template, $email->getHtmlTemplate());
                $this->assertEquals($context, $email->getContext());

                // Most importantly, verify the X-Transport header is set to 'alternative'
                $headers = $email->getHeaders();
                $this->assertTrue($headers->has('X-Transport'));
                $this->assertEquals('alternative', $headers->get('X-Transport')->getBodyAsString());

                return true;
            }));

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }
}
