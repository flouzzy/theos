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
        if ($ips === false || count($ips) === 0) {
            return null;
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return null;
            }
        }

        return $ips[0];
    }

    public function downloadFileByUrl(string $fileUrl, string $mediaType = 'post'): string|false
    {
        $targetDirectory = $this->getTargetDirectory($mediaType);
        $fileFullPath = null;

        try {
            $content = $this->fetchFileContent($fileUrl);
            if (!$content) {
                return false;
            }

            return $this->saveDownloadedContent($content, $targetDirectory, $fileFullPath);
        } catch (FileException $e) {
            $this->logDownloadError('Erreur lors du téléchargement du fichier', $e, $fileUrl, $fileFullPath);
        } catch (TransportExceptionInterface $e) {
            $this->logDownloadError('Error downloading file (Transport)', $e, $fileUrl);
        } catch (\Throwable $e) {
            $this->logDownloadError('Error downloading file', $e, $fileUrl);
        }

        return false;
    }

    private function fetchFileContent(string $url): ?string
    {
        $maxRedirects = 3;

        for ($i = 0; $i <= $maxRedirects; $i++) {
            $parts = parse_url($url);
            if (!$parts || !isset($parts['host'])) {
                return null;
            }

            $host = $parts['host'];
            // Default ports: 80 for http, 443 for https
            $scheme = $parts['scheme'] ?? 'http';
            $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);

            // Allow only standard ports to reduce attack surface
            if (!in_array($port, [80, 443, 8080], true)) {
                return null;
            }

            $safeIp = $this->getSafeIp($host);
            if ($safeIp === null) {
                return null;
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
                    return null;
                }

                // Handle relative redirects
                if (str_starts_with($location, '/')) {
                    $portString = isset($parts['port']) ? ':' . $parts['port'] : '';
                    $location = $scheme . '://' . $host . $portString . $location;
                } elseif (!preg_match('/^https?:\/\//i', $location)) {
                    // Reject complex relative paths for safety
                    return null;
                }

                $url = $location;
                continue;
            }

            return null;
        }

        return null;
    }

    private function saveDownloadedContent(
        string $content,
        string $targetDirectory,
        ?string &$fileFullPath
    ): string|false
    {
        // Verify content type
        if (!class_exists(\finfo::class) || !defined('FILEINFO_MIME_TYPE')) {
            return false; // Fallback if finfo is missing, though it's standard.
        }
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

        $extension = $extensions[$mimeType];

        // Generate safe filename
        $filename = uniqid('media_', true) . '.' . $extension;
        $fileFullPath = $targetDirectory . '/' . $filename;

        $fileDownloaded = file_put_contents($fileFullPath, $content);

        if ($fileDownloaded !== false) {
            try {
                $this->imageOptimizer->resize($fileFullPath, ['maxWidth' => 800, 'maxHeight' => 600]);
            } catch (\Throwable $e) {
                // Ignore resize error, keep the file as it's a valid image
            }
            return explode('public/', $fileFullPath)[1];
        }

        return false;
    }

    private function logDownloadError(
        string $message,
        \Throwable $exception,
        string $fileUrl,
        ?string $fileFullPath = null
    ): void
    {
        /**
         * @var \App\Entity\User|null $user
         */
        $user = $this->security->getUser();
        $context = [
            'user_email' => $user ? $user->getEmail() : 'anonymous',
            'error_message' => $exception->getMessage(),
            'fileUrl' => $fileUrl,
        ];

        if ($fileFullPath !== null) {
            $context['fileFullPath'] = $fileFullPath;
        }

        $this->logger->error($message, $context);
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

        // On retourne l'image téléchargée ou la ligne directe (distante) vers l'image
        return $fileDownloaded ?? $imageUrl;
    }
}
