<?php

namespace App\Controller\Admin;

use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Repository\ModuleCompletionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    public function index(
        UserRepository $userRepository,
        ModuleCompletionRepository $moduleCompletionRepository,
        CompletionRepository $completionRepository,
        CourseCompletionRepository $courseCompletionRepository
    ): Response {
        return $this->render('admin/dashboard/index.html.twig', [
            // Select all verified user
            'usersTotal' => $userRepository->countVerifiedUsers(),
            'coursesTotal' => $courseCompletionRepository->countCoursesCompletions(),
            'modulesTotal' => $moduleCompletionRepository->countModuleCompletions(),
            'lessonsTotal' => $completionRepository->countLessonsCompletions(),
        ]);
    }
}
