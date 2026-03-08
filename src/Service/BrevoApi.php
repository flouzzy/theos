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
        private string $brevoApiKey,
        private string $brevoFromName,
        private string $brevoFromEmail,
        private string $brevoSubject,
        private string $brevoListId,
        private string $kernelEnvironment,
        private LoggerInterface $logger,
        private SettingRepository $settingRepository
    ) {
        $config = BrevoClient\Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->brevoApiKey);

        $this->httpClient = new Client();

        $this->apiContact = new Api\ContactsApi(
            $this->httpClient,
            $config
        );

        $this->apiEmail = new BrevoClient\Api\TransactionalEmailsApi(
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
        if (in_array($this->kernelEnvironment, ['dev', 'test'])) {
            $this->logger->info('Skipping Brevo contact addition in ' . $this->kernelEnvironment . ' environment');
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
            $listIds = array_map('intval', explode(',', $this->brevoListId));
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
        if (in_array($this->kernelEnvironment, ['dev', 'test'])) {
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
        if ($this->brevoApiKey === "null") {
            return;
        }

        $subject = $params['subject'] ?? $this->brevoSubject;
        $htmlContent = $params['html_content'] ?? '<html><body><h1>Email from Le Rocher Académie</h1></body></html>';

        $sendSmtpEmail = new BrevoClient\Model\SendSmtpEmail([
            "sender" => [
                "name" => $this->brevoFromName,
                "email" => $this->brevoFromEmail
            ],
            'htmlContent' => $htmlContent,
            "params" => $params,
            "to" => $tos,
            'subject' => $subject
        ]);

        try {
            $this->apiEmail->sendTransacEmail($sendSmtpEmail);
            $this->logger->info('Email successfully sent via Brevo API to: ' . json_encode($tos));
        } catch (Exception $e) {
            $this->logger->error('Exception when calling TransactionalEmailsApi->sendTransacEmail: ' . $e->getMessage());
        }
    }
}
