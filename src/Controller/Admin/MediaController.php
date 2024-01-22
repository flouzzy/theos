<?php

namespace App\Controller\Admin;

use App\Service\MediaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/media', name: 'admin_media_')]
class MediaController extends AbstractController
{
    #[Route('/new', name: 'new', methods: ['POST'])]
    public function new(Request $request, MediaManager $mediaManager): Response
    {
        $codeError = 500;
        $imageFile = $request->files->get('file');
        $imageUrl = null;
        if ($imageFile) {
            $imageUrl = $mediaManager->upload($imageFile, 'lesson', ['maxWidth' => 800, 'maxHeight' => 800]);
            $codeError = 200;
        }

        return $this->json(
            [
                'location' => $imageUrl
            ],
            $codeError
        );
    }
}
