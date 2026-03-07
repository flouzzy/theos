<?php

namespace App\Tests\SmokeTest;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminSmokeTest extends WebTestCase
{
    public function testAllAdminPagesLoadSuccessfully(): void
    {
        $client = static::createClient();
        
        // 1. Authenticate as Super Admin
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        
        $adminEmail = 'charles@edounze.com';
        $adminPart = $em->getRepository(User::class)->findOneBy(['email' => $adminEmail]);
        
        if (!$adminPart) {
            $adminPart = new User();
            $adminPart->setEmail($adminEmail);
            $adminPart->setFirstname('Admin');
            $adminPart->setLastname('Super');
            $adminPart->setRoles(['ROLE_SUPER_ADMIN']);
            $adminPart->setPassword('password');
            $em->persist($adminPart);
            $em->flush();
        }

        $client->loginUser($adminPart);
        
        // 2. Fetch Dashboard
        $client->request('GET', '/admin');
        
        $this->assertResponseIsSuccessful('The Admin Dashboard should load successfully.');
        
        // 3. Fetch all admin GET routes from the Router definition
        /** @var \Symfony\Component\Routing\RouterInterface $router */
        $router = $container->get('router');
        $routes = $router->getRouteCollection()->all();
        
        $urlsToTest = [];
        foreach ($routes as $name => $route) {
            $path = $route->getPath();
            $methods = $route->getMethods();
            
            // Only test GET routes under /admin without wildcard parameters required
            if (str_starts_with($path, '/admin') 
                && (empty($methods) || in_array('GET', $methods))
                && strpos($path, '{') === false // Skip routes needing IDs like /admin/user/{id}
            ) {
                // Ignore logout or explicit non-safe paths just in case
                if (strpos($path, '/logout') !== false) continue;
                
                $urlsToTest[] = $path;
            }
        }
        
        $this->assertNotEmpty($urlsToTest, 'Should find admin routes to test.');
        
        // 4. Test each URL
        foreach ($urlsToTest as $path) {
            $client->request('GET', $path);
            $this->assertResponseIsSuccessful(sprintf('Failed to load admin page: %s', $path));
        }
    }
}
