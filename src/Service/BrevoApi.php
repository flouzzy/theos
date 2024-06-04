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
    private $apiContact;
    private $apiEmail;
    private $httpClient;
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger
    ) {
        $config = BrevoClient\Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->parameterBag->get('brevo_api_key'));

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

        // $result = $this->apiContact->getAccount();
        // dd($result);
    }

    public function addOrUpdateContact(User $user)
    {
        $createContact = new BrevoClient\Model\CreateContact();
        $createContact->setEmail($user->getEmail());
        $createContact->setExtId((string)$user->getId());
        $createContact->setUpdateEnabled(true);
        $createContact->setListIds(array_map('intval', explode(',', $this->parameterBag->get('brevo_list_id'))));
        $createContact->setAttributes([
            'PRENOM' => $user->getFirstname(),
            'NOM' => $user->getLastname()
        ]);

        try {
            return $this->apiContact->createContact($createContact);
        } catch (Exception $e) {
            $this->logger->error('Exception when calling ContactsApi->createContact: ' . $e->getMessage());
        }
    }

    public function sendEmail(array $tos, array $params)
    {
        if ($this->parameterBag->get('brevo_api_key') === "null") {
            return;
        }

        // $templateId = $params['templateId'] ?? $this->parameterBag->get('brevo_template_id');

        $subject = $params['subject'] ?? $this->parameterBag->get('brevo_subject');

        $sendSmtpEmail = new BrevoClient\Model\SendSmtpEmail([
            "sender" => [
                "name" => $this->parameterBag->get('brevo_from_name'),
                "email" => $this->parameterBag->get('brevo_from_email')
            ],
            // "templateId" => (int) $templateId,
            'htmlContent' => '<html><body><h1>This is a transactional email {{params.content}}</h1></body></html>',
            "params" => $params,
            "to" => [$tos],
            'subject' => $subject
        ]);

        try {
            $result = $this->apiEmail->sendTransacEmail($sendSmtpEmail);
            dump($result);
            // return $this->httpClient->request('POST', 'https://api.brevo.com/v3/smtp/email', [
            //     'headers' => [
            //         // 'http_version' => CURL_HTTP_VERSION_1_1,
            //         'content-type' => 'application/json',
            //         'accept'  => 'application/json',
            //         'api-key'   => $this->parameterBag->get('brevo_api_key'),
            //         'return_transfer' => true
            //     ],
            //     'body'  => json_encode($data, JSON_THROW_ON_ERROR),
            //     'max_redirects' => 10,
            //     'timeout'   => 30,
            // ]);
        } catch (Exception $e) {
            $this->logger->error('Exception when calling TransactionalEmailsApi->sendTransacEmail: ' . $e->getMessage());
            dump($e);
        }
    }
}
