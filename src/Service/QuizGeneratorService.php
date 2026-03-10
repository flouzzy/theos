<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Lesson;
use App\Entity\Quiz;
use App\Entity\QuizQuestion;
use App\Entity\QuizOption;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class QuizGeneratorService
{
    private const MODEL = 'gemini-1.5-flash';
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ':generateContent';

    public function __construct(
        #[Autowire(env: 'GEMINI_API_KEY')]
        private string $apiKey,
        private EntityManagerInterface $entityManager,
        private ?Client $client = null
    ) {
        $this->client = $client ?? new Client();
    }

    /**
     * Génère un quiz pour une leçon donnée.
     */
    public function generateQuiz(Lesson $lesson, int $numQuestions = 5): Quiz
    {
        $content = $lesson->getTranscript() ?? strip_tags($lesson->getContent() ?? '');
        
        $prompt = <<<PROMPT
Génère un quiz de $numQuestions questions à choix multiples basé sur le contenu suivant :
"$content"

Réponds UNIQUEMENT au format JSON avec la structure suivante :
{
  "title": "Titre du quiz",
  "questions": [
    {
      "text": "Texte de la question",
      "options": [
        {"text": "Option 1", "isCorrect": true},
        {"text": "Option 2", "isCorrect": false},
        {"text": "Option 3", "isCorrect": false},
        {"text": "Option 4", "isCorrect": false}
      ]
    }
  ]
}
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json'
            ]
        ];

        try {
            $response = $this->client->post(self::API_URL . '?key=' . $this->apiKey, [
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $jsonContent = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$jsonContent) {
                throw new \Exception("Gemini n'a pas renvoyé de contenu JSON.");
            }

            $quizData = json_decode($jsonContent, true);
            
            return $this->createQuizFromData($lesson, $quizData);

        } catch (\Exception $e) {
            throw new \Exception("Erreur lors de la génération du quiz : " . $e->getMessage());
        }
    }

    private function createQuizFromData(Lesson $lesson, array $data): Quiz
    {
        $quiz = new Quiz();
        $quiz->setTitle($data['title'] ?? 'Quiz pour ' . $lesson->getTitle());
        $quiz->setLesson($lesson);
        $quiz->setModule($lesson->getModule());

        foreach ($data['questions'] ?? [] as $qData) {
            $question = new QuizQuestion();
            $question->setText($qData['text']);
            $quiz->addQuestion($question);

            foreach ($qData['options'] ?? [] as $oData) {
                $option = new QuizOption();
                $option->setText($oData['text']);
                $option->setIsCorrect($oData['isCorrect']);
                $question->addOption($option);
            }
            
            $this->entityManager->persist($question);
        }

        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        return $quiz;
    }
}
