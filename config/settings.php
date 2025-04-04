<?php

use Monolog\Logger;

$settings = [
    'displayErrorDetails' => true, // Should be set to false in production
    'logErrors' => true,
    'logErrorDetails' => true,
'logger' => [
        'name' => 'app',
        'path' => 'php://stderr',
        'level' => Logger::DEBUG,
    ],
    'blob' => [
        'accountName' => 'devstoreaccount1',
        'accountKey' => 'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==',
        'protocol' => 'http',
        'endpoint' => 'http://localhost:10000/devstoreaccount1',
    ],
];

$localFile = __DIR__ . '/settings.local.php';

if (!file_exists($localFile)) {
    return $settings;
}

return array_merge($settings, include $localFile);