<?php
require 'vendor/autoload.php';
use App\Entity\Course;
use App\Entity\Module;
use App\Entity\Lesson;
use App\Kernel;

$kernel = new Kernel('dev', true);
$kernel->boot();
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

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
    $course->setDescription('Parcours d\'apprentissage sur ' . $courseTitle);
    $entityManager->persist($course);

    foreach ($modulesData as $moduleTitle => $lessonsData) {
        $module = new Module();
        $module->setTitle($moduleTitle);
        $course->addModule($module);
        $entityManager->persist($module);

        foreach ($lessonsData as $lessonTitle) {
            $lesson = new Lesson();
            $lesson->setTitle($lessonTitle);
            $lesson->setContent('Contenu détaillé de la leçon : ' . $lessonTitle);
            $module->addLesson($lesson);
            $entityManager->persist($lesson);
        }
    }
}
$entityManager->flush();
echo "Data created successfully!";
