<?php

// Désactiver l'utilisation des ACL pour éviter les problèmes de permissions dans Docker
$_SERVER['SYMFONY_SKIP_ACL_SET_PERMISSIONS'] = '1';

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
