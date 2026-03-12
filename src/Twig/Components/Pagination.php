<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Pagination')]
final class Pagination
{
    public int $currentPage;
    public int $totalPages;
    public string $routeName;
    public array $routeParams = [];
}
