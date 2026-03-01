<?php
$content = file_get_contents('src/Service/MediaManager.php');

$search = <<<'SEARCH'
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

            $content = null;
            $url = $fileUrl;
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
                    $content = $response->getContent();
                    break;
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

            if (!$content) {
                return false;
            }

            // Verify content type
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
SEARCH;

$replace = <<<'REPLACE'
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
            $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

            // Allow only standard ports to reduce attack surface
            if (!in_array($port, [80, 443, 8080])) {
                return null;
            }

            $safeIp = $this->getSafeIp($host);
            if (!$safeIp) {
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
                    $location = $scheme . '://' . $host . ($parts['port'] ?? '' ? ':' . $parts['port'] : '') . $location;
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

    private function saveDownloadedContent(string $content, string $targetDirectory, ?string &$fileFullPath): string|false
    {
        // Verify content type
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

        if ($fileDownloaded) {
            try {
                $this->imageOptimizer->resize($fileFullPath, ['maxWidth' => 800, 'maxHeight' => 600]);
            } catch (\Throwable $e) {
                // Ignore resize error, keep the file as it's a valid image
            }
            return explode('public/', $fileFullPath)[1];
        }

        return false;
    }

    private function logDownloadError(string $message, \Throwable $exception, string $fileUrl, ?string $fileFullPath = null): void
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
REPLACE;

$newContent = str_replace($search, $replace, $content);
file_put_contents('src/Service/MediaManager.php', $newContent);

if ($content === $newContent) {
    echo "No changes made.\n";
} else {
    echo "Replaced successfully.\n";
}
