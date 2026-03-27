<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GeminiAudioService
{
    private const MODEL = 'gemini-2.5-flash-preview-tts';
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ':generateContent';

    public function __construct(
        #[Autowire(env: 'GEMINI_API_KEY')]
        private string $apiKey,
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%/public/uploads/audio/lessons')]
        private string $audioDir,
        private ?Client $client = null
    ) {
        $this->client = $client ?? new Client();
    }

    /**
     * @param Lesson $lesson
     * @param string|null $voiceName Nom de la voix (ex: Charon, Kore, Puck)
     * @param string|null $directorNotes Instructions de mise en scène
     */
    public function generateAudio(Lesson $lesson, ?string $voiceName = 'Charon', ?string $directorNotes = null): string
    {
        // Clean HTML content for speech
        $text = strip_tags($lesson->getContent());
        // Simple cleanup of multiple spaces/newlines
        $text = preg_replace('/\s+/', ' ', $text);
        
        if ($directorNotes) {
            $text = "$directorNotes: \"$text\"";
        } else {
            // Default pedagogical tone
            $text = "Lis de façon pédagogique, calme et posée : \"$text\"";
        }

        $base64Audio = $this->callGeminiApi($text, $voiceName);
        $pcmData = base64_decode($base64Audio);

        $fileName = $this->convertPcmToMp3($pcmData, $lesson->getSlug());
        $outputPath = $this->audioDir . '/' . $fileName;

        // Update Lesson entity
        $lesson->setAudioPath('uploads/audio/lessons/' . $fileName);

        // Optionally get duration using ffprobe
        $lesson->setAudioDuration($this->getAudioDuration($outputPath));

        $this->entityManager->flush();

        return $lesson->getAudioPath();
    }

    private function callGeminiApi(string $text, ?string $voiceName): string
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $text]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseModalities' => ['AUDIO'],
                'speechConfig' => [
                    'voiceConfig' => [
                        'prebuiltVoiceConfig' => [
                            'voiceName' => $voiceName
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->client->post(self::API_URL . '?key=' . $this->apiKey, [
            'json' => $payload
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        $base64Audio = $result['candidates'][0]['content']['parts'][0]['inlineData']['data'] ?? null;

        if (!$base64Audio) {
            throw new \Exception("Aucune donnée audio reçue de Gemini.");
        }

        return $base64Audio;
    }

    private function convertPcmToMp3(string $pcmData, string $slug): string
    {
        $tempPcmFile = tempnam(sys_get_temp_dir(), 'gemini_audio_') . '.pcm';
        file_put_contents($tempPcmFile, $pcmData);

        $fileName = $slug . '-' . uniqid() . '.mp3';
        $outputPath = $this->audioDir . '/' . $fileName;

        // Gemini TTS outputs PCM s16le, 24000Hz, Mono.
        // We convert it to MP3 using ffmpeg.
        $process = $this->createProcess([
            'ffmpeg',
            '-y', 
            '-f', 's16le', 
            '-ar', '24000', 
            '-ac', '1', 
            '-i', $tempPcmFile, 
            '-codec:a', 'libmp3lame', 
            '-qscale:a', '2', 
            $outputPath
        ]);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            unlink($tempPcmFile);
            throw new \Exception("La conversion ffmpeg a échoué: " . $exception->getMessage());
        }

        // Clean up temp file
        unlink($tempPcmFile);

        return $fileName;
    }

    protected function createProcess(array $command): Process
    {
        return new Process($command);
    }

    private function getAudioDuration(string $filePath): ?int
    {
        $process = $this->createProcess([
            'ffprobe', 
            '-v', 'error', 
            '-show_entries', 'format=duration', 
            '-of', 'default=noprint_wrappers=1:nokey=1', 
            $filePath
        ]);

        try {
            $process->mustRun();
            return (int) round((float) $process->getOutput());
        } catch (\Exception $e) {
            return null;
        }
    }
}
