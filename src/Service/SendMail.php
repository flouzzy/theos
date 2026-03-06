<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SendMail
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private BrevoApi $brevoApi,
        private ParameterBagInterface $parameterBag
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
        $toAddresses = is_array($to) ? $to : [$to];

        $email = (new TemplatedEmail())
            ->from($from)
            ->to(...$toAddresses)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        // On envoie le mail
        try {
            $apiKey = $this->parameterBag->get('brevo_api_key');
            
            // Si pas de clé Brevo ou environnement dev/test, on utilise le mailer classique
            if (!$apiKey || $apiKey === 'null' || in_array($this->parameterBag->get('kernel.environment'), ['dev', 'test'])) {
                 $this->mailer->send($email);
                 return;
            }

            $toList = [];
            if (is_string($to)) {
                $toList[] = ['email' => $to];
            } else {
                foreach ($to as $recipient) {
                    if ($recipient instanceof Address) {
                        $toList[] = ['email' => $recipient->getAddress(), 'name' => $recipient->getName()];
                    } else {
                        $toList[] = ['email' => $recipient];
                    }
                }
            }
            $this->brevoApi->sendEmail($toList, $context);
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
