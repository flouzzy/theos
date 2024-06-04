<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SendMail
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private BrevoApi $brevoApi
    ) {
    }

    public function send(
        Address|string $from,
        array|string $to,
        string $subject,
        string $template,
        array $context
    ): void {
        //On crée le mail
        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        // On envoie le mail
        try {
            // $this->mailer->send($email);
            $toList = [];
            if (is_array($to)) {
                $toList = ['email' => $to];
            } else {
                foreach ($to as $email) {
                    $toList[] = ['email' => $email];
                }
            }
            $this->brevoApi->sendEmail();
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
            $this->logger->error($e->getDebug());

            // ... use the transport "alternative":
            $email->getHeaders()->addTextHeader('X-Transport', 'alternative');
            $this->mailer->send($email);
        }
    }
}
