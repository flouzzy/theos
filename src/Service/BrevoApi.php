<?php

namespace App\Service;

use App\Entity\User;
use Brevo\Client as BrevoClient;

use Brevo\Client\Api;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class BrevoApi
{
    private $apiInstance;
    public function __construct(private LoggerInterface $logger)
    {
        $config = BrevoClient\Configuration::getDefaultConfiguration()->setApiKey('api-key', $_ENV['BREVO_API_KEY']);

        $this->apiInstance = new Api\ContactsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new Client(),
            $config
        );

        // $result = $this->apiInstance->getAccount();
        // dd($result);
    }

    public function addOrUpdateContact(User $user)
    {
        $createContact = new BrevoClient\Model\CreateContact();
        $createContact->setEmail($user->getEmail());
        $createContact->setExtId((string)$user->getId());
        $createContact->setUpdateEnabled(true);
        $createContact->setListIds(array_map('intval', explode(',', $_ENV['BREVO_LIST_ID'])));
        $createContact->setAttributes([
            'PRENOM' => $user->getFirstname(),
            'NOM' => $user->getLastname()
        ]);

        try {
            return $this->apiInstance->createContact($createContact);
        } catch (Exception $e) {
            $this->logger->error('Exception when calling ContactsApi->createContact: ' . $e->getMessage());
        }
    }
}
