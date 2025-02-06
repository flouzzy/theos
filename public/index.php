<?php

use App\Kernel;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';


// Custom events listener
$dispatcher = new EventDispatcher();
$lessonSubcriber = new \App\EventSubscriber\LessonSubscriber();
$dispatcher->addSubscriber($lessonSubcriber);


return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
