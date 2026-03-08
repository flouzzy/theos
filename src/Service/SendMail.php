<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class SendMail
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private BrevoApi $brevoApi,
        private ParameterBagInterface $parameterBag,
        private Environment $twig
    ) {
    }

    public function send(
        Address|string $from,
        array|string $to,
        string $subject,
        string $template,
        array $context
    ): void {
        $this->logger->info('Attempting to send email: ' . $subject . ' to ' . (is_array($to) ? json_encode($to) : $to));
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

            // Pour Brevo, on doit d'abord rendre le template Twig en HTML
            $htmlContent = $this->twig->render($template, $context);

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
            
            // On ajoute le sujet et le contenu HTML au contexte pour BrevoApi
            $context['subject'] = $subject;
            $context['html_content'] = $htmlContent;

            $this->brevoApi->sendEmail($toList, $context);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            // En cas d'erreur avec Brevo ou le rendu, on tente le mailer classique
            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface $te) {
                $this->logger->error($te->getDebug());
            }
        }
    }
}
