<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Lesson;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RecommendationController extends AbstractController
{
    #[Route('/lesson/{id}/recommendations', name: 'app_lesson_recommendations')]
    public function index(Lesson $lesson, RecommendationService $recommendationService): Response
    {
        $recommendations = $recommendationService->getRecommendations($lesson, 3);

        return $this->render('lesson/_recommendations.html.twig', [
            'recommendations' => $recommendations,
        ]);
    }
}
