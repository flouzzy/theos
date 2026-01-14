<?php

use App\Kernel;
use App\Entity\User;
use Doctrine\DBAL\Connection;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    $container = $kernel->getContainer();

    $hasher = $container->get('security.user_password_hasher');
    /** @var Connection $connection */
    $connection = $container->get('doctrine.dbal.default_connection');
    
    // Create a dummy user object just to satisfy hasher interface if needed, 
    // or just fetch the user to get salt/algo if configured (modern hashers generally don't need salt).
    // But password hasher requires UserInterface usually.
    // So let's fetch the user entity to be clean.
    $em = $container->get('doctrine')->getManager();
    $user = $em->getRepository(User::class)->findOneBy(['email' => 'charles@edounze.com']);
    
    if (!$user) {
        echo "User charles@edounze.com not found.\n";
        return;
    }

    $hashedPassword = $hasher->hashPassword($user, 'test123');
    
    // DBAL Update
    $connection->executeStatement(
        'UPDATE "user" SET password = :password WHERE email = :email',
        [
            'password' => $hashedPassword,
            'email' => 'charles@edounze.com'
        ]
    );

    echo "Password updated successfully for charles@edounze.com\n";
};
