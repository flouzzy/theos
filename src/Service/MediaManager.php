<?php

declare(strict_types=1);

// src/Service/MediaManager.php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MediaManager
{
    private string $targetDirectory;
    private SluggerInterface $slugger;
    private Filesystem $filesystem;

    public function __construct(
        string $targetDirectory,
        SluggerInterface $slugger,
        private ImageOptimizer $imageOptimizer,
        private Security $security,
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
        ?Filesystem $filesystem = null
    ) {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function upload(UploadedFile $file, string $mediaType = 'course', array $params = []): ?string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = strtolower((string) $this->slugger->slug($originalFilename));
        $extension = $file->guessExtension();

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            throw new FileException('Invalid file extension: ' . $extension);
        }

        $fileName = $safeFilename . '-' . uniqid() . '.' . $extension;
        $targetDirectory = $this->getTargetDirectory($mediaType);

        // On récupère uniquement le chemin après dossier public
        $fileDirectory = explode('public/', $targetDirectory)[1];

        try {
            $file->move($targetDirectory, $fileName);
            $this->imageOptimizer->resize("$targetDirectory/$fileName", $params);
        } catch (FileException $exception) {
            // ... handle exception if something happens during file upload
            /**
             * @var \App\Entity\User|null $user
             */
            $user = $this->security->getUser();
            $this->logger->error(
                'Failed to upload file ' . $file->getClientOriginalName() . ': ' . $exception->getMessage(),
                [
                    'user_email' => $user ? $user->getEmail() : 'anonymous',
                    'error_message' => $exception->getMessage()
                ]
            );
        }

        return "$fileDirectory/$fileName";
    }

    /**
     * Suppression d'un fichier
     *
     * @param string $fileName
     * @param string $mediaType
     * @return void
     */
    public function deleteFile(string $fileName, string $mediaType = 'post'): void
    {
        try {
            $this->filesystem->remove($this->getTargetDirectory($mediaType) . "/$fileName");
        } catch (IOException $e) {
            // ... handle exception if something happens during file removal
        }
    }

    public function getTargetDirectory(?string $mediaType = null): string
    {
        return $mediaType ? "$this->targetDirectory/$mediaType" : $this->targetDirectory;
    }

    private function getSafeIp(string $host): ?string
    {
        $ips = gethostbynamel($host);
        if ($ips === false || empty($ips)) {
            return null;
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return null;
            }
        }

        return $ips[0];
    }

    private function fetchUrlContent(string $url): string|false
    {
        $maxRedirects = 3;

        for ($i = 0; $i <= $maxRedirects; $i++) {
            $parts = parse_url($url);
            if (!$parts || !isset($parts['host'])) {
                return false;
            }

            $host = $parts['host'];
            // Default ports: 80 for http, 443 for https
            $scheme = $parts['scheme'] ?? 'http';
            $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

            // Allow only standard ports to reduce attack surface
            if (!in_array($port, [80, 443, 8080])) {
                return false;
            }

            $safeIp = $this->getSafeIp($host);
            if (!$safeIp) {
                return false;
            }

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 30,
                'max_redirects' => 0,
                'resolve' => [$host => $safeIp],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return $response->getContent();
            }

            if ($statusCode >= 300 && $statusCode < 400) {
                $headers = $response->getHeaders(false);
                $location = $headers['location'][0] ?? null;
                if (!$location) {
                    return false;
                }

                // Handle relative redirects
                if (str_starts_with($location, '/')) {
                    $location = $scheme . '://' . $host . ($parts['port'] ?? '' ? ':' . $parts['port'] : '') . $location;
                } elseif (!preg_match('/^https?:\/\//i', $location)) {
                    // Reject complex relative paths for safety
                    return false;
                }

                $url = $location;
                continue;
            }

            return false;
        }

        return false;
    }

    private function verifyAndGetExtension(string $content): string|false
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($content);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        if (!isset($extensions[$mimeType])) {
            return false;
        }

        return $extensions[$mimeType];
    }

    public function downloadFileByUrl(string $fileUrl, ?string $mediaType = null): string|false
    {
        // Check for valid protocol
        if (!preg_match('/^https?:\/\//i', $fileUrl)) {
            return false;
        }

        // Validate mediaType to prevent directory traversal
        if ($mediaType && !preg_match('/^[a-zA-Z0-9_-]+$/', $mediaType)) {
            return false;
        }

        /** @var string|null $fileFullPath */
        $fileFullPath = null;
        try {
            $targetDirectory = $this->getTargetDirectory($mediaType);

            // Get current directory
            if (!is_dir($targetDirectory)) {
                mkdir($targetDirectory, 0777, true);
            }

            $content = $this->fetchUrlContent($fileUrl);

            if (!$content) {
                return false;
            }

            $extension = $this->verifyAndGetExtension($content);

            if (!$extension) {
                return false;
            }

            // Generate safe filename
            $filename = uniqid('media_', true) . '.' . $extension;
            $fileFullPath = $targetDirectory . '/' . $filename;

            $fileDownloaded = file_put_contents($fileFullPath, $content);

            if ($fileDownloaded) {
                try {
                    $this->imageOptimizer->resize($fileFullPath, ['maxWidth' => 800, 'maxHeight' => 600]);
                } catch (\Throwable $e) {
                    // Ignore resize error, keep the file as it's a valid image
                }
                return explode('public/', $fileFullPath)[1];
            }
        } catch (FileException $exception) {
            // ... handle exception if something happens during file download
            /**
             * @var \App\Entity\User|null $user
             */
            $user = $this->security->getUser();
            $this->logger->error(
                'Erreur lors du téléchargement du fichier',
                [
                    'user_email' => $user ? $user->getEmail() : 'anonymous',
                    'error_message' => $exception->getMessage(),
                    'fileUrl' => $fileUrl, 'fileFullPath' => $fileFullPath
                ]
            );
        } catch (TransportExceptionInterface $e) {
            /**
             * @var \App\Entity\User|null $user
             */
            $user = $this->security->getUser();
            $this->logger->error(
                'Error downloading file (Transport)',
                [
                    'user_email' => $user ? $user->getEmail() : 'anonymous',
                    'error_message' => $e->getMessage(),
                    'fileUrl' => $fileUrl
                ]
            );
        } catch (\Throwable $e) {
            // Catch other exceptions
            /**
             * @var \App\Entity\User|null $user
             */
            $user = $this->security->getUser();
            $this->logger->error(
                'Error downloading file',
                [
                    'user_email' => $user ? $user->getEmail() : 'anonymous',
                    'error_message' => $e->getMessage(),
                    'fileUrl' => $fileUrl
                ]
            );
        }

        return false;
    }

    /**
     * Download image file from text/html content
     *
     * @param string $content
     * @return string
     */
    public function downloadImageFromContent(string $content, string $mediaType = 'post'): ?string
    {
        // Extract image URL from content
        $fileDownloaded = null;
        $imageUrl = null;
        preg_match('/<img.*?src=[\'"](.*?)[\'"].*?>/i', $content, $matches);
        if (isset($matches[1])) {
            // On extrait la première image que l'on trouve dans le contenu
            $imageUrl = explode('?', $matches[1])[0];
            if (!empty($imageUrl)) {
                $fileDownloaded = $this->downloadFileByUrl($imageUrl, $mediaType);
            }
        }

        if ($fileDownloaded === false) {
            return $imageUrl;
        }

        // On retourne l'image téléchargée ou le lien direct (distant) vers l'image
        return $fileDownloaded ?? $imageUrl;
    }
}
