<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Cohort;
use App\Entity\Course;
use App\Service\EngagementAnalyzer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/analytics', name: 'admin_analytics_')]
#[IsGranted('ROLE_ADMIN')]
class AtRiskDashboardController extends AbstractController
{
    #[Route('/at-risk/{id}', name: 'at_risk')]
    public function atRisk(Cohort $cohort, EngagementAnalyzer $engagementAnalyzer): Response
    {
        $atRiskStudents = $engagementAnalyzer->getAtRiskStudents($cohort, 30); // Seuil à 30 pour voir plus de données

        return $this->render('admin/instructor/at_risk.html.twig', [
            'cohort' => $cohort,
            'students' => $atRiskStudents,
        ]);
    }

    #[Route('/at-risk', name: 'cohort_select')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $cohorts = $entityManager->getRepository(Cohort::class)->findAll();

        return $this->render('admin/instructor/cohort_select.html.twig', [
            'cohorts' => $cohorts,
        ]);
    }

    #[Route('/content-efficacy/{id}', name: 'content_efficacy')]
    public function contentEfficacy(Course $course, EngagementAnalyzer $engagementAnalyzer): Response
    {
        $efficacyData = $engagementAnalyzer->getContentEfficacy($course);

        return $this->render('admin/instructor/content_efficacy.html.twig', [
            'course' => $course,
            'efficacyData' => $efficacyData,
        ]);
    }

    #[Route('/content-efficacy', name: 'course_select')]
    public function courseSelect(EntityManagerInterface $entityManager): Response
    {
        $courses = $entityManager->getRepository(Course::class)->findAll();

        return $this->render('admin/instructor/course_select.html.twig', [
            'courses' => $courses,
        ]);
    }
}
