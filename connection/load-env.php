<?php
function loadEnv($path)
{
    // Check if essential env vars already exist
    $requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    $envSet = true;

    foreach ($requiredVars as $var) {
        if (getenv($var) === false) {
            $envSet = false;
            break;
        }
    }

    // Skip loading .env file if all required vars are already set
    if ($envSet) {
        return;
    }

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

