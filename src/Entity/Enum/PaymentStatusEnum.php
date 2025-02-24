<?php

namespace App\Entity\Enum;

enum PaymentStatusEnum: string
{
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case IN_PROGRESS = 'in_progress';
    case REFUSED = 'refused';
}
