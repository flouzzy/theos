<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Module;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $courses = [
            'La Divinité de Jésus' => [
                'Fondements bibliques' => ['L\'annonce dans l\'AT', 'Les déclarations de Jésus', 'Le témoignage de Jean'],
                'Les implications théologiques' => ['L\'incarnation', 'Le salut par la divinité', 'La médiation'],
                'Jésus dans l\'histoire' => ['Les Pères de l\'Église', 'Le Concile de Nicée', 'Jésus aujourd\'hui'],
            ],
            'Comment bien gérer ses finances' => [
                'Principes de base' => ['Le budget personnel', 'Épargner intelligemment', 'Gérer ses dettes'],
                'Investissement et foi' => ['La vision biblique de l\'argent', 'La générosité', 'Investir avec sagesse'],
                'Planification long terme' => ['La retraite', 'La transmission', 'La liberté financière'],
            ],
            'L\'art de vaincre la peur' => [
                'Comprendre la peur' => ['Les origines de la peur', 'Peurs saines et peurs nuisibles', 'La biologie de l\'anxiété'],
                'La perspective spirituelle' => ['Le courage dans les Écritures', 'La paix de Dieu', 'La prière face à l\'inquiétude'],
                'Application pratique' => ['Techniques de respiration', 'Exposer ses peurs', 'La confiance en soi'],
            ],
        ];

        foreach ($courses as $courseTitle => $modulesData) {
            $course = new Course();
            $course->setTitle($courseTitle);
            $course->setDescription('Parcours d\'apprentissage sur ' . $courseTitle);
            $manager->persist($course);

            foreach ($modulesData as $moduleTitle => $lessonsData) {
                $module = new Module();
                $module->setTitle($moduleTitle);
                $module->setCourse($course);
                $manager->persist($module);

                foreach ($lessonsData as $lessonTitle) {
                    $lesson = new Lesson();
                    $lesson->setTitle($lessonTitle);
                    $lesson->setContent('Contenu détaillé de la leçon : ' . $lessonTitle);
                    $lesson->setModule($module);
                    $manager->persist($lesson);
                }
            }
        }

        $manager->flush();
    }
}
