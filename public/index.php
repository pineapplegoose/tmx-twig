<?php

use App\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    error_log(">>> Symfony booting at " . date('Y-m-d H:i:s'));

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
