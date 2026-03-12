<?php
$content = file_get_contents('src/Controller/Admin/LessonController.php');
$search = <<<EOT
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(LessonRepository \$lessonRepository): Response
    {
        return \$this->render('admin/lesson/index.html.twig', [
            'lessons' => \$lessonRepository->findAllWithModules(),
        ]);
    }
EOT;
$replace = <<<EOT
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(LessonRepository \$lessonRepository, \App\Service\PaginatorService \$paginatorService): Response
    {
        \$qb = \$lessonRepository->findAllWithModulesQueryBuilder();
        \$pagination = \$paginatorService->paginate(\$qb);

        return \$this->render('admin/lesson/index.html.twig', [
            'lessons' => \$pagination['data'],
            'currentPage' => \$pagination['currentPage'],
            'totalPages' => \$pagination['totalPages'],
        ]);
    }
EOT;

file_put_contents('src/Controller/Admin/LessonController.php', str_replace($search, $replace, $content));
