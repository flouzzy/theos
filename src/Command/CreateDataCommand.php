<?php

namespace App\Command;

use App\Entity\Course;
use App\Entity\Module;
use App\Entity\Lesson;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-data')]
class CreateDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $author = $this->userRepository->findOneBy(['email' => 'temp@test.com']);
        if (!$author) {
            $output->writeln('Author not found!');
            return Command::FAILURE;
        }

        $courses = [
            'Divinite de Jesus' => [
                'Fondements' => ['Annonce AT', 'Declarations Jesus', 'Temoignage Jean'],
                'Theologie' => ['Incarnation', 'Salut', 'Mediation'],
                'Histoire' => ['Peres Eglise', 'Nicee', 'Jesus aujourdhui'],
            ],
            'Gestion Finances' => [
                'Base' => ['Budget', 'Epargne', 'Dettes'],
                'Investissement' => ['Vision biblique', 'Generosite', 'Sagesse'],
                'Planification' => ['Retraite', 'Transmission', 'Liberte'],
            ],
            'Vaincre la peur' => [
                'Comprendre' => ['Origines', 'Peur saine', 'Biologie'],
                'Spirituel' => ['Courage', 'Paix Dieu', 'Priere'],
                'Pratique' => ['Respiration', 'Exposer peur', 'Confiance'],
            ],
        ];

        foreach ($courses as $courseTitle => $modulesData) {
            $course = new Course();
            $course->setTitle($courseTitle);
            $course->setAuthor($author);
            $course->setDescription('Parcours d\'apprentissage sur ' . $courseTitle);
            $this->entityManager->persist($course);

            foreach ($modulesData as $moduleTitle => $lessonsData) {
                $module = new Module();
                $module->setTitle($moduleTitle);
                $course->addModule($module);
                $this->entityManager->persist($module);

                foreach ($lessonsData as $lessonTitle) {
                    $lesson = new Lesson();
                    $lesson->setTitle($lessonTitle);
                    $lesson->setContent('Contenu détaillé de la leçon : ' . $lessonTitle);
                    $module->addLesson($lesson);
                    $this->entityManager->persist($lesson);
                }
            }
        }
        $this->entityManager->flush();
        $output->writeln('Data created!');
        return Command::SUCCESS;
    }
}
