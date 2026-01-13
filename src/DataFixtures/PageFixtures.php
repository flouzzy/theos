<?php

namespace App\DataFixtures;

use App\Entity\Page;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $pages = [
            [
                'title' => 'Mentions légales',
                'slug' => 'mentions-legales',
                'content' => '<h1>Mentions légales</h1><p>Lorem ipsum dolor sit amet...</p>'
            ],
            [
                'title' => 'Conditions générales d\'utilisation',
                'slug' => 'cgu',
                'content' => '<h1>CGU</h1><p>Conditions générales d\'utilisation...</p>'
            ],
            [
                'title' => 'Politique de confidentialité',
                'slug' => 'politique-de-confidentialite',
                'content' => '<h1>Politique de confidentialité</h1><p>Politique de confidentialité...</p>'
            ]
        ];

        foreach ($pages as $data) {
            $page = new Page();
            $page->setTitle($data['title']);
            $page->setSlug($data['slug']);
            $page->setContent($data['content']);
            $manager->persist($page);
        }

        $manager->flush();
    }
}
