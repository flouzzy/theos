<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function getSettings(): Setting
    {
        $setting = $this->findOneBy([]);

        if (!$setting) {
            $setting = new Setting();
            $this->getEntityManager()->persist($setting);
            $this->getEntityManager()->flush();
        }

        return $setting;
    }
}
