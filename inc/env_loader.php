<?php
/**
 * Simple .env file loader
 * Loads environment variables from a .env file into $_ENV and putenv()
 */
function loadEnvFile($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    return true;
}

/**
 * Get an environment variable with a default fallback
 */
function env($key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    return $value;
}
