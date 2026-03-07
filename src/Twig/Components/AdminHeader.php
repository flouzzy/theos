<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('AdminHeader')]
final class AdminHeader
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $title = '';

    #[LiveProp]
    public string $subtitle = '';

    #[LiveProp]
    public ?string $addPath = null;

    #[LiveProp]
    public ?string $addLabel = null;

    #[LiveProp]
    public ?string $backPath = null;

    #[LiveProp(writable: true)]
    public string $query = '';

    /**
     * Cette méthode pourrait être étendue pour retourner des résultats de recherche
     * globale dans le futur (Command Palette style).
     */
    public function getResults(): array
    {
        if (strlen($this->query) < 2) {
            return [];
        }

        // Simulé pour l'instant
        return [];
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->query = '';
    }
}
