<?php

namespace App\Service;

use GeminiAPI\Client;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\ModelName;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Repository\SiteSettingRepository;

class CoachAIAgent
{
    private Client $client;

    public function __construct(
        #[Autowire(env: 'GEMINI_API_KEY')]
        private string $apiKey,
        #[Autowire(env: 'GEMINI_MODEL')]
        private string $geminiModel,
        private \App\Repository\SiteSettingRepository $siteSettingRepo,
        private \Symfony\Contracts\Cache\CacheInterface $cache
    ) {
        $this->client = new Client($this->apiKey);
    }

    public function getHistory(\App\Entity\User $user): array
    {
        return $this->cache->get('ai_coach_history_' . $user->getId(), function () {
            return [];
        });
    }

    public function saveHistory(\App\Entity\User $user, array $history): void
    {
        $item = $this->cache->getItem('ai_coach_history_' . $user->getId());
        $item->set($history);
        $item->expiresAfter(new \DateInterval('P7D')); // 7 days of persistence
        $this->cache->save($item);
    }

    public function generateResponse(\App\Entity\User $user, string $newMessage): string
    {
        $history = $this->getHistory($user);

        // Setup initial system instructions from Database
        $setting = $this->siteSettingRepo->findOneBy(['name' => 'COACH_PROMPT']);
        $systemPrompt = $setting && $setting->getValue() ? $setting->getValue() : 
            "Tu es un coach pédagogique francophone travaillant pour Le Rocher Académie, une école de théologie. Ton rôle est d'encourager l'étudiant de façon concise et conviviale.";

        // Format history into Gemini API Content objects
        $geminiHistory = [];
        
        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? Role::User : Role::Model;
            $geminiHistory[] = Content::text($msg['content'], $role);
        }

        try {
            // We use the beta version to enable system instructions
            $model = $this->client
                ->withV1BetaVersion()
                ->generativeModel($this->geminiModel)
                ->withSystemInstruction($systemPrompt);

            $chat = $model->startChat();

            if (!empty($geminiHistory)) {
                $chat = $chat->withHistory($geminiHistory);
            }

            $response = $chat->sendMessage(new TextPart($newMessage));
            $reply = $response->text();

            // Update and save history
            $history[] = ['role' => 'user', 'content' => $newMessage];
            $history[] = ['role' => 'model', 'content' => $reply];
            $this->saveHistory($user, $history);

            return $reply;
        } catch (\Exception $e) {
            // Return a fallback message in case the API call fails or the API key isn't provided
            return "Désolé, une erreur est survenue lors de ma réflexion. (Erreur Technique: " . $e->getMessage() . ")";
        }
    }

    public function generateNextStepNudge(\App\Entity\User $user, \App\Entity\Lesson $lesson): string
    {
        $prompt = sprintf(
            "Génère une phrase d'encouragement très courte (max 150 caractères) pour l'étudiant %s afin qu'il étudie sa prochaine leçon : '%s' du module '%s'. Sois motivant, biblique et chaleureux.",
            $user->getFullname(),
            $lesson->getTitle(),
            $lesson->getModule()->getTitle()
        );

        try {
            $model = $this->client->generativeModel($this->geminiModel);
            $response = $model->generateContent(new TextPart($prompt));
            return trim($response->text());
        } catch (\Exception $e) {
            return "C'est le moment idéal pour découvrir votre prochaine leçon : " . $lesson->getTitle() . " !";
        }
    }
}
