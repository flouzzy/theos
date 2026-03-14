<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AvatarFrame;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AvatarFrameFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $frames = [
            [
                'name' => 'Bronze Enthusiast',
                'identifier' => 'bronze',
                'cssClass' => 'border-orange-600 ring-2 ring-orange-400 ring-offset-2',
            ],
            [
                'name' => 'Silver Scholar',
                'identifier' => 'silver',
                'cssClass' => 'border-gray-400 ring-2 ring-gray-200 ring-offset-2',
            ],
            [
                'name' => 'Gold Master',
                'identifier' => 'gold',
                'cssClass' => 'border-yellow-500 ring-4 ring-yellow-200 ring-offset-2 shadow-[0_0_15px_rgba(234,179,8,0.5)]',
            ],
            [
                'name' => 'Dedicated Learner',
                'identifier' => 'dedicated',
                'cssClass' => 'border-blue-500 ring-4 ring-blue-300 ring-offset-2 animate-pulse',
            ],
            [
                'name' => 'Unstoppable Fire',
                'identifier' => 'unstoppable',
                'cssClass' => 'border-red-600 ring-4 ring-orange-500 ring-offset-2 shadow-[0_0_20px_rgba(220,38,38,0.7)] animate-bounce',
            ],
        ];

        foreach ($frames as $frameData) {
            $frame = new AvatarFrame();
            $frame->setName($frameData['name']);
            $frame->setIdentifier($frameData['identifier']);
            $frame->setCssClass($frameData['cssClass']);
            $manager->persist($frame);
        }

        $manager->flush();
    }
}
