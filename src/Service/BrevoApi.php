<?php

namespace App\Service;

use App\Entity\User;
use Brevo\Client as BrevoClient;

use Brevo\Client\Api;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BrevoApi
{
    private Api\ContactsApi $apiContact;
    private BrevoClient\Api\TransactionalEmailsApi $apiEmail;
    private Client $httpClient;

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger
    ) {
        $apiKey = $this->parameterBag->get('brevo_api_key');
        if (!is_string($apiKey)) {
            $apiKey = '';
        }

        $config = BrevoClient\Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);

        $this->httpClient = new Client();

        $this->apiContact = new Api\ContactsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            $this->httpClient,
            $config
        );

        $this->apiEmail = new BrevoClient\Api\TransactionalEmailsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            $this->httpClient,
            $config
        );

    }

    public function addOrUpdateContact(User $user): void
    {
        // Skip Brevo operations in dev/test environments to avoid polluting data
        $env = $this->parameterBag->get('kernel.environment');
        if (in_array($env, ['dev', 'test'])) {
            $envStr = is_string($env) ? $env : 'unknown';
            $this->logger->info('Skipping Brevo contact addition in ' . $envStr . ' environment');
            return;
        }

        $email = $user->getEmail();
        if (!$email) {
            return;
        }

        $createContact = new BrevoClient\Model\CreateContact();
        $createContact->setEmail($email);
        $createContact->setExtId((string)$user->getId());
        $createContact->setUpdateEnabled(true);

        $listIdConfig = $this->parameterBag->get('brevo_list_id');
        if (is_string($listIdConfig)) {
             $createContact->setListIds(array_map('intval', explode(',', $listIdConfig)));
        }

        $createContact->setAttributes((object) [
            'PRENOM' => $user->getFirstname(),
            'NOM' => $user->getLastname()
        ]);

        try {
            $this->apiContact->createContact($createContact);
        } catch (Exception $e) {
            $this->logger->error('Exception when calling ContactsApi->createContact: ' . $e->getMessage());
        }
    }

    /**
     * @param array<int, array<string, string>> $tos
     * @param array<string, mixed> $params
     */
    public function sendEmail(array $tos, array $params): void
    {
        if ($this->parameterBag->get('brevo_api_key') === "null") {
            return;
        }

        $subject = $params['subject'] ?? $this->parameterBag->get('brevo_subject');

        $sendSmtpEmail = new BrevoClient\Model\SendSmtpEmail([
            "sender" => [
                "name" => $this->parameterBag->get('brevo_from_name'),
                "email" => $this->parameterBag->get('brevo_from_email')
            ],
            'htmlContent' => '<html><body><h1>This is a transactional email {{params.content}}</h1></body></html>',
            "params" => $params,
            "to" => $tos,
            'subject' => $subject
        ]);

        try {
            $this->apiEmail->sendTransacEmail($sendSmtpEmail);
        } catch (Exception $e) {
            $this->logger->error('Exception when calling TransactionalEmailsApi->sendTransacEmail: ' . $e->getMessage());
        }
    }
}
