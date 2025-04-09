<?php

use Dotenv\Dotenv;
use Monolog\Logger;

// Load values from .env into environmental variables
(Dotenv::createImmutable(__DIR__ . '/..'))->safeLoad();

RETURN [
    'displayErrorDetails' => (bool) ($_ENV['APP_DISPLAY_ERROR_DETAILS'] ?? true), // Should be set to false in production
    'logErrors' => (bool) ($_ENV['APP_LOG_ERRORS'] ?? true),
    'logErrorDetails' => (bool) ($_ENV['APP_LOG_ERROR_DETAILS'] ?? true),
'logger' => [
        'name' => $_ENV['LOGGER_NAME'] ?? 'app',
        'path' => $_ENV['LOGGER_PATH'] ?? 'php://stderr',
        'level' => (int) $_ENV['LOGGER_LEVEL'] ?? Logger::DEBUG,
    ],
    'blob' => [
        'accountName' => $_ENV['BLOB_ACCOUNT_NAME'] ?? 'devstoreaccount1',
        'accountKey' => $_ENV['BLOB_ACCOUNT_KEY'] ?? 'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==',
        'protocol' => $_ENV['BLOB_PROTOCOL'] ?? 'http',
        'endpoint' => $_ENV['BLOB_ENDPOINT'] ?? 'http://localhost:10000/devstoreaccount1p',
    ],
];
