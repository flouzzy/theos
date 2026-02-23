<?php

namespace App\Service;

use Exception;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ImageOptimizer
{
    private const MAX_WIDTH = 800;
    private const MAX_HEIGHT = 800;

    private Imagine $imagine;

    public function __construct(private LoggerInterface $logger, private Security $security)
    {
        $this->imagine = new Imagine();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function resize(string $filename, array $params = []): void
    {
        try {
            /** @var array{0: int, 1: int}|false $imageSize */
            $imageSize = getimagesize($filename);
            if ($imageSize === false) {
                return;
            }
            list($iwidth, $iheight) = $imageSize;
            $ratio = $iwidth / $iheight;
            /** @var int $width */
            $width = $params['maxWidth'] ?? self::MAX_WIDTH;
            /** @var int $height */
            $height = $params['maxHeight'] ?? self::MAX_HEIGHT;

            if ($width / $height > $ratio) {
                $width = (int) ($height * $ratio);
            } else {
                $height = (int) ($width / $ratio);
            }

            $photo = $this->imagine->open($filename);
            $photo->resize(new Box($width, $height))->save($filename);
        } catch (Exception $exception) {
            /**
             * @var \App\Entity\User|null $user
             */
            $user = $this->security->getUser();
            $this->logger->error(
                'Failed to upload file ' . $filename . ': ' . $exception->getMessage(),
                [
                    'user_email' => $user ? $user->getEmail() : 'anonymous',
                    'error_message' => $exception->getMessage()
                ]
            );
        }
    }
}
