<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// Carga manual del archivo .env que está en la raíz del proyecto
(new Dotenv())->load(dirname(__DIR__, 2) . '/.env');