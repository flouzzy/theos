<?php

namespace App\Service;

use App\Entity\Cohort;
use App\Entity\User;
use App\Repository\CohortRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class CohortSession
{
    private const SESSION_KEY = 'active_cohort_id';

    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
        private CohortRepository $cohortRepository
    ) {
    }

    public function getSelectedCohort(): ?Cohort
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $session = $this->requestStack->getSession();
        $cohortId = $session->get(self::SESSION_KEY);

        if ($cohortId) {
            $cohort = $this->cohortRepository->find($cohortId);
            // Vérifier que l'utilisateur appartient bien à cette cohorte
            if ($cohort && $user->getCohorts()->contains($cohort)) {
                return $cohort;
            }
        }

        // Si aucune cohorte en session ou accès non autorisé, on prend la première de l'utilisateur
        $cohort = $user->getCohorts()->first() ?: null;
        if ($cohort) {
            $this->setSelectedCohort($cohort);
        }

        return $cohort;
    }

    public function setSelectedCohort(Cohort $cohort): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY, $cohort->getId());
    }

    public function clearSelectedCohort(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove(self::SESSION_KEY);
    }
}
