<?php

namespace App\Service;

use GeminiAPI\Client;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\ModelName;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CoachAIAgent
{
    private Client $client;

    public function __construct(
        #[Autowire(env: 'GEMINI_API_KEY')]
        private string $apiKey
    ) {
        $this->client = new Client($this->apiKey);
    }

    public function generateResponse(array $history, string $newMessage): string
    {
        // Setup initial system instructions
        $systemPrompt = "Tu es un coach pédagogique francophone travaillant pour Le Rocher Académie. "
            . "Ton rôle est d'encourager l'étudiant, de répondre à ses questions sur le code ou le développement de façon concise "
            . "et conviviale. Ne donne pas de réponses longues (> 100 mots par message) sauf si c'est très technique. "
            . "Utilise des emojis de temps en temps.";

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
                ->generativeModel(ModelName::GEMINI_1_5_FLASH)
                ->withSystemInstruction($systemPrompt);

            $chat = $model->startChat();

            if (!empty($geminiHistory)) {
                $chat = $chat->withHistory($geminiHistory);
            }

            $response = $chat->sendMessage(new TextPart($newMessage));

            return $response->text();
        } catch (\Exception $e) {
            // Return a fallback message in case the API call fails or the API key isn't provided
            return "Désolé, une erreur est survenue lors de ma réflexion. (Erreur Technique: " . $e->getMessage() . ")";
        }
    }
}
