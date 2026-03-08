<?php

namespace App\Service;

use App\Repository\SettingRepository;
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
        private LoggerInterface $logger,
        private SettingRepository $settingRepository
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

    /**
     * @param array<int>|null $listIds
     */
    public function addOrUpdateContact(User $user, ?array $listIds = null): void
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

        if ($listIds === null) {
            $listIds = [];
            $listIdConfig = $this->parameterBag->get('brevo_list_id');
            if (is_string($listIdConfig)) {
                $listIds = array_map('intval', explode(',', $listIdConfig));
            }
        }

        if (!empty($listIds)) {
            $createContact->setListIds($listIds);
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

    public function addContactToOnboardedList(User $user): void
    {
        $setting = $this->settingRepository->getSettings();
        $listId = $setting->getBrevoListOnboarded();

        if ($listId) {
            $this->addOrUpdateContact($user, [(int) $listId]);
        }
    }

    public function moveToAlumniList(User $user): void
    {
        $setting = $this->settingRepository->getSettings();
        $onboardedListId = $setting->getBrevoListOnboarded();
        $alumniListId = $setting->getBrevoListAlumni();

        if ($onboardedListId) {
            $this->removeContactFromList($user, (int) $onboardedListId);
        }

        if ($alumniListId) {
            $this->addOrUpdateContact($user, [(int) $alumniListId]);
        }
    }

    public function removeContactFromList(User $user, int $listId): void
    {
        // Skip Brevo operations in dev/test environments
        $env = $this->parameterBag->get('kernel.environment');
        if (in_array($env, ['dev', 'test'])) {
            return;
        }

        $email = $user->getEmail();
        if (!$email) {
            return;
        }

        $contactEmails = new BrevoClient\Model\RemoveContactFromList();
        $contactEmails->setEmails([$email]);

        try {
            $this->apiContact->removeContactFromList($listId, $contactEmails);
        } catch (Exception $e) {
            $this->logger->error('Exception when calling ContactsApi->removeContactFromList: ' . $e->getMessage());
        }
    }

    /**
     * @param array<int, mixed> $tos
     * @param array<string, mixed> $params
     */
    public function sendEmail(array $tos, array $params): void
    {
        if ($this->parameterBag->get('brevo_api_key') === "null") {
            return;
        }

        $subject = $params['subject'] ?? $this->parameterBag->get('brevo_subject');
        $htmlContent = $params['html_content'] ?? '<html><body><h1>Email from Le Rocher Académie</h1></body></html>';

        $sendSmtpEmail = new BrevoClient\Model\SendSmtpEmail([
            "sender" => [
                "name" => $this->parameterBag->get('brevo_from_name'),
                "email" => $this->parameterBag->get('brevo_from_email')
            ],
            'htmlContent' => $htmlContent,
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
