<?php
$content = file_get_contents('src/Controller/Admin/NotificationController.php');
$search = <<<EOT
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(NotificationRepository \$notificationRepository): Response
    {
        return \$this->render('admin/notification/index.html.twig', [
            'notifications' => \$notificationRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }
EOT;
$replace = <<<EOT
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(NotificationRepository \$notificationRepository, \App\Service\PaginatorService \$paginatorService): Response
    {
        \$qb = \$notificationRepository->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC');

        \$pagination = \$paginatorService->paginate(\$qb);

        return \$this->render('admin/notification/index.html.twig', [
            'notifications' => \$pagination['data'],
            'currentPage' => \$pagination['currentPage'],
            'totalPages' => \$pagination['totalPages'],
        ]);
    }
EOT;

file_put_contents('src/Controller/Admin/NotificationController.php', str_replace($search, $replace, $content));
