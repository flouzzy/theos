<?php

namespace App\Twig\Components;

use App\Entity\Lesson;
use App\Service\RecommendationService;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Recommendation')]
class Recommendation
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Lesson $lesson = null;

    public function __construct(
        private RecommendationService $recommendationService
    ) {
    }

    /**
     * @return Lesson[]
     */
    public function getRecommendations(): array
    {
        if (!$this->lesson) {
            return [];
        }

        return $this->recommendationService->getRecommendations($this->lesson, 3);
    }
}
