<?php

use App\apps\security\Kernel;
$autoloadRuntime = realpath(__DIR__ . '/../../../vendor/autoload_runtime.php');

require_once $autoloadRuntime;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
