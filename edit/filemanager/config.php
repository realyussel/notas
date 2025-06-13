<?php
// Configuration for filemanager access using environment variables or .env file

// Load .env file if present
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = array_map('trim', explode('=', $line, 2));
        if (! isset($_ENV[$key])) {
            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

$useAuthEnv = getenv('USE_AUTH');
$use_auth   = $useAuthEnv !== false ? filter_var($useAuthEnv, FILTER_VALIDATE_BOOLEAN) : true;

$auth_users = [];
$usersEnv   = getenv('AUTH_USERS');
if ($usersEnv !== false) {
    $pairs = array_filter(array_map('trim', explode(',', $usersEnv)));
    foreach ($pairs as $pair) {
        list($user, $pass) = array_map('trim', explode(':', $pair, 2));
        if ($user !== '') {
            $auth_users[$user] = $pass;
        }
    }
} else {
    // Default credentials (should be overridden in .env)
    $auth_users = ['realyussel' => 'realyussel'];
}

return [
    'use_auth'   => $use_auth,
    'auth_users' => $auth_users,
];
