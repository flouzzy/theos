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

    public function testSendSuccessfullyViaBrevoApi(): void
    {
        $from = new Address('from@example.com', 'From User');
        $to = [new Address('to@example.com', 'To User')];
        $subject = 'Test Subject';
        $template = 'test_template.html.twig';
        $context = ['key' => 'value'];

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->with(
                [['email' => 'to@example.com', 'name' => 'To User']],
                $context
            );

        $this->mailer->expects($this->never())
            ->method('send');

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }

    public function testSendWithTransportExceptionFallsBackToMailer(): void
    {
        $from = new Address('from@example.com', 'From User');
        $to = 'to@example.com';
        $subject = 'Test Subject';
        $template = 'test_template.html.twig';
        $context = ['key' => 'value'];

        $exception = new TransportException('Transport failed');

        $this->brevoApi->expects($this->once())
            ->method('sendEmail')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exception->getDebug());

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($from, $to, $subject, $template, $context) {
                $hasAlternativeHeader = $email->getHeaders()->has('X-Transport') &&
                    $email->getHeaders()->get('X-Transport')->getBody() === 'alternative';

                return $hasAlternativeHeader
                    && $email->getFrom()[0]->getAddress() === $from->getAddress()
                    && $email->getTo()[0]->getAddress() === $to
                    && $email->getSubject() === $subject
                    && $email->getHtmlTemplate() === $template
                    && $email->getContext() === $context;
            }));

        $this->sendMail->send($from, $to, $subject, $template, $context);
    }
}
