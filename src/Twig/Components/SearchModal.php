<?php

namespace App\Twig\Components;

use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('SearchModal')]
class SearchModal
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    public function __construct(
        private CourseRepository $courseRepository,
        private LessonRepository $lessonRepository
    ) {
    }

    public function getResults(): array
    {
        if (strlen($this->query) < 2) {
            return [];
        }

        $courses = $this->courseRepository->createQueryBuilder('c')
            ->where('c.title LIKE :query')
            ->orWhere('c.description LIKE :query')
            ->setParameter('query', '%' . $this->query . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $lessons = $this->lessonRepository->createQueryBuilder('l')
            ->where('l.title LIKE :query')
            ->orWhere('l.content LIKE :query')
            ->setParameter('query', '%' . $this->query . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return [
            'courses' => $courses,
            'lessons' => $lessons,
        ];
    }
}
