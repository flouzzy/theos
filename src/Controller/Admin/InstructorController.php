<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\CohortRepository;
use App\Service\EngagementAnalyzer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/instructor', name: 'admin_instructor_')]
class InstructorController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(
        CohortRepository $cohortRepository,
        EngagementAnalyzer $engagementAnalyzer
    ): Response {
        $cohorts = $cohortRepository->findAll();
        $cohortData = [];

        foreach ($cohorts as $cohort) {
            $atRisk = $engagementAnalyzer->getAtRiskStudents($cohort);
            $cohortData[] = [
                'cohort' => $cohort,
                'atRiskCount' => count($atRisk),
                'atRiskStudents' => array_slice($atRisk, 0, 5), // Top 5
                'totalStudents' => count($cohort->getUsers())
            ];
        }

        return $this->render('admin/instructor/dashboard.html.twig', [
            'cohortData' => $cohortData
        ]);
    }

    #[Route('/cohort/{id}', name: 'cohort_detail')]
    public function cohortDetail(
        int $id,
        CohortRepository $cohortRepository,
        \App\Repository\LessonRepository $lessonRepository,
        EngagementAnalyzer $engagementAnalyzer
    ): Response {
        $cohort = $cohortRepository->find($id);
        if (!$cohort) {
            throw $this->createNotFoundException('Cohort non trouvée');
        }

        $atRisk = $engagementAnalyzer->getAtRiskStudents($cohort, 30);
        
        $modules = [];
        foreach ($cohort->getCourses() as $course) {
            foreach ($course->getModules() as $module) {
                $modules[] = $module;
            }
        }

        $allStats = $lessonRepository->findEfficacyStatsByModules($modules);
        $statsByModuleId = [];
        foreach ($allStats as $stat) {
            $moduleId = $stat['moduleId'];
            // remove moduleId from the stat array to match the original return shape
            unset($stat['moduleId']);
            $statsByModuleId[$moduleId][] = $stat;
        }

        $efficacyData = [];
        foreach ($modules as $module) {
            $efficacyData[$module->getTitle()] = $statsByModuleId[$module->getId()] ?? [];
        }

        return $this->render('admin/instructor/cohort_detail.html.twig', [
            'cohort' => $cohort,
            'atRisk' => $atRisk,
            'efficacyData' => $efficacyData
        ]);
    }
}
