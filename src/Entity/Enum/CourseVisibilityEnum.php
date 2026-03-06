<?php

namespace App\Entity\Enum;

enum CourseVisibilityEnum: string
{
    case PUBLIC = 'public';
    case RESTRICTED = 'restricted';
}
