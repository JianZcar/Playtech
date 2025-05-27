<?php
function loadEnv($path)
{
    if (!file_exists($path)) {
        throw new Exception("Environment file not found.");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        putenv("$key=$value");
    }
}

$envPath = __DIR__ . '/../.env';
loadEnv($envPath);
