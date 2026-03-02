<?php

namespace App\Repository;

use App\Entity\PaymentSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentSetting>
 *
 * @method PaymentSetting|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentSetting|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentSetting[]    findAll()
 * @method PaymentSetting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentSetting::class);
    }
}
