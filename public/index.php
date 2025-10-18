<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    // In dev/debug mode allow unlimited execution time to avoid hitting PHP\'s max execution
    // time limit while running long requests or migrations during development.
    if (($context['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null) === 'dev' || ($context['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false)) {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
    }

    return $kernel;
};
