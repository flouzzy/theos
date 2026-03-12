<?php
$content = file_get_contents('src/Controller/Admin/NoteController.php');
$search = <<<EOT
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(NoteRepository \$noteRepository): Response
    {
        return \$this->render('admin/note/index.html.twig', [
            // Return all notes order by createdAt DESC
            'notes' => \$noteRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }
EOT;
$replace = <<<EOT
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(NoteRepository \$noteRepository, \App\Service\PaginatorService \$paginatorService): Response
    {
        \$qb = \$noteRepository->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC');

        \$pagination = \$paginatorService->paginate(\$qb);

        return \$this->render('admin/note/index.html.twig', [
            'notes' => \$pagination['data'],
            'currentPage' => \$pagination['currentPage'],
            'totalPages' => \$pagination['totalPages'],
        ]);
    }
EOT;

file_put_contents('src/Controller/Admin/NoteController.php', str_replace($search, $replace, $content));
