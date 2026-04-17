<?php

namespace App\Controller\Admin;

use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Repository\ModuleCompletionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/completion', name: 'admin_completion_')]
class CompletionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'], priority: 3)]
    public function index(
        CompletionRepository $completionRepository,
        ModuleCompletionRepository $moduleCompletionRepository,
        CourseCompletionRepository $courseCompletionRepository
    ): Response {
        return $this->render('admin/completion/index.html.twig', [
            'lessonsCompletion' => $completionRepository->findBy([], ['id' => 'DESC'], 10),
            'modulesCompletion' => $moduleCompletionRepository->findBy([], ['id' => 'DESC'], 10),
            'coursesCompletion' => $courseCompletionRepository->findBy([], ['id' => 'DESC'], 10),
        ]);
    }

    #[Route('/lesson', name: 'lesson', methods: ['GET'])]
    public function showLessons(
        Request $request,
        CompletionRepository $completionRepository,
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $paginator = $completionRepository->findPaginated($page, $limit);
        $totalItems = count($paginator);
        $totalPages = (int) ceil($totalItems / $limit);

        return $this->render('admin/completion/show.html.twig', [
            'completions' => $paginator,
            'title' => 'Lessons completions',
            'completionType' => 'lesson',
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/module', name: 'module', methods: ['GET'])]
    public function showModules(
        ModuleCompletionRepository $completionRepository,
    ): Response {
        return $this->render('admin/completion/show.html.twig', [
            'completions' => $completionRepository->findBy([], ['id' => 'DESC']),
            'title' => 'Modules completions',
            'completionType' => 'module'
        ]);
    }

    #[Route('/course', name: 'course', methods: ['GET'])]
    public function showCourses(
        CourseCompletionRepository $completionRepository,
    ): Response {
        return $this->render('admin/completion/show.html.twig', [
            'completions' => $completionRepository->findBy([], ['id' => 'DESC']),
            'title' => 'Courses completions',
            'completionType' => 'course'
        ]);
    }
}
