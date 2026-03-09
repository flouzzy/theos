<?php

namespace App\Twig\Components;

use App\Entity\Cohort;
use App\Entity\User;
use App\Repository\CohortRepository;
use App\Repository\CourseRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('CourseCatalog')]
class CourseCatalog
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public string $query = '';

    #[LiveProp(writable: true, url: true)]
    public ?int $cohortId = null;

    #[LiveProp]
    public array $subscribedCourseIds = [];

    public function __construct(
        private CourseRepository $courseRepository,
        private CohortRepository $cohortRepository,
        private Security $security
    ) {
    }

    public function mount(): void
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $this->subscribedCourseIds = array_map(
                fn($course) => $course->getId(),
                $user->getCourses()->toArray()
            );
        }
    }

    public function getCourses(): array
    {
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $activeCohorts = [];

        if ($this->cohortId) {
            $selectedCohort = $this->cohortRepository->find($this->cohortId);
            if ($selectedCohort) {
                $activeCohorts = [$selectedCohort];
            }
        } else {
            // Si pas de filtre explicite, on utilise les cohortes de l'utilisateur normal
            if (!$isAdmin && $this->security->getUser()) {
                /** @var User $user */
                $user = $this->security->getUser();
                $activeCohorts = $user->getCohorts()->toArray();
            }
        }

        return $this->courseRepository->findCatalogCourses($activeCohorts, $isAdmin, $this->query);
    }

    public function getSelectedCohortTitle(): string
    {
        if ($this->cohortId) {
            $cohort = $this->cohortRepository->find($this->cohortId);
            return $cohort ? $cohort->getTitle() : 'Toutes les promos';
        }
        return 'Toutes les promos';
    }

    public function getCohorts(): array
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->cohortRepository->findBy([], ['title' => 'ASC']);
        }
        
        $user = $this->security->getUser();
        if ($user) {
            /** @var User $user */
            return $user->getCohorts()->toArray();
        }

        return [];
    }
}
