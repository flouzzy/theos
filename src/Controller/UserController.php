<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user/{username}', name: 'user_profile')]
    public function profile(
        string $username,
        UserRepository $userRepository,
        CompletionRepository $completionRepository,
        CourseCompletionRepository $courseCompletionRepository
    ): Response {
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Calculate Stats
        $coursesEnrolled = $user->getCourses();
        $completedCoursesCount = $courseCompletionRepository->countCompletedCoursesForUser($user);
        $notesCount = $user->getNotes()->count();

        $totalMinutes = $completionRepository->countTotalDurationByUser($user);
        $learningHours = floor($totalMinutes / 60);

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'stats' => [
                'enrolled' => $coursesEnrolled->count(),
                'completed' => $completedCoursesCount,
                'hours' => $learningHours,
                'notes' => $notesCount,
                'xp' => $user->getXp(),
                'streak' => $user->getStreak(),
            ],
            'badges' => $user->getBadges(),
        ]);
    }
}
