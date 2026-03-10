<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\JWT;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWT $jwt
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password required'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Generate or get JWT secret for user
        if (!$user->getJwtSecret()) {
            $user->setJwtSecret(base64_encode(random_bytes(32)));
            $entityManager->flush();
        }

        $token = $jwt->generate(
            ['typ' => 'JWT', 'alg' => 'HS256'],
            ['user_id' => $user->getId(), 'email' => $user->getEmail()],
            $user->getJwtSecret()
        );

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
            ]
        ]);
    }

    #[Route('/courses', name: 'courses', methods: ['GET'])]
    public function courses(Request $request, JWT $jwt, EntityManagerInterface $entityManager): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Bearer token required'], Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($authHeader, 7);
        if (!$jwt->isValid($token)) {
            return new JsonResponse(['error' => 'Invalid token format'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $jwt->getPayload($token);
        $user = $entityManager->getRepository(User::class)->find($payload['user_id']);

        if (!$user || !$jwt->check($token, $user->getJwtSecret())) {
            return new JsonResponse(['error' => 'Invalid token or expired'], Response::HTTP_UNAUTHORIZED);
        }

        if ($jwt->isExpired($token)) {
            return new JsonResponse(['error' => 'Token expired'], Response::HTTP_UNAUTHORIZED);
        }

        $courses = $user->getCourses();
        $data = [];
        foreach ($courses as $course) {
            $data[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'slug' => $course->getSlug(),
                'description' => $course->getExcerpt(),
            ];
        }

        return new JsonResponse($data);
    }
}
