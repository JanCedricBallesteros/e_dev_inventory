<?php
ifexist_ini_set("error_log",  dirname(__DIR__, 1) . "/upload/logs/php-error.log");
$envFile = dirname(__DIR__) . '/.envi'; // The environment file
$htaccessFile = dirname(__DIR__) . '/.htaccess'; // The .htaccess file
$templates = ['DEV', 'PROD'];

// Initialize configuration array
$config = [];

// Check if the .envi file exists
if (!file_exists($envFile)) {
    $defaultContent = "ENV=PROD\nHTACCESS=DEV"; // Default content for the .envi file
    if (file_put_contents($envFile, $defaultContent) === false) {
        error_log("Failed to create .envi file.");
        http_response_code(500);
        exit();
    }
    error_log(".envi file created with default settings.");
}

// Read and parse the .envi file
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    list($key, $value) = explode('=', $line, 2);
    $config[trim($key)] = trim($value);
}

// Retrieve environment and flag settings
$env = strtoupper($config['ENV'] ?? 'DEV');
$flag = strtoupper($config['HTACCESS'] ?? 'DEV');

if (!in_array($env, $templates)) {
    error_log("Invalid environment setting in .envi file: $env.\n");
    http_response_code(500);
    exit;
}

// Determine new content based on environment
if ($env === 'PROD') {
    $newContent = <<<'EOD'
    # Custom rules for prod environment
    RewriteCond %{HTTP_REFERER} !^https?://e_dev_inventory.com/.*$ [NC]
    EOD;
} else {
    $newContent = <<<'EOD'
    # Custom rules for development environment
    RewriteCond %{HTTP_REFERER} !^http?://localhost/e_dev_inventory/.*$ [NC]
    EOD;
}

// Check if the environment has changed
if ($env !== $flag) {
    $currentHtaccessContent = file_exists($htaccessFile) ? file($htaccessFile, FILE_IGNORE_NEW_LINES) : [];

    $inSection = false;
    $updatedContent = [];
    $startMarker = '#start';
    $endMarker = '#end';

    foreach ($currentHtaccessContent as $line) {
        if (trim($line) === $startMarker) {
            $inSection = true;
            $updatedContent[] = $startMarker;
            $updatedContent[] = $newContent; // Insert new content
            continue;
        }
        if (trim($line) === $endMarker) {
            $inSection = false;
            $updatedContent[] = $endMarker;
            continue;
        }
        if (!$inSection) {
            $updatedContent[] = $line; // Keep lines outside the section
        }
    }

    if (file_put_contents($htaccessFile, implode(PHP_EOL, $updatedContent) . PHP_EOL) !== false) {
        error_log("Successfully updated .htaccess to $env environment.\n");
        $config['HTACCESS'] = $env; // Update the flag
        $newEnvContent = [];
        foreach ($config as $key => $value) {
            $newEnvContent[] = "$key=$value";
        }

        if (file_put_contents($envFile, implode(PHP_EOL, $newEnvContent) . PHP_EOL) !== false) {
            error_log("Successfully updated .envi file to reflect the new HTACCESS setting.\n");
        } else {
            error_log("Failed to write setting in .envi file: $env.\n");
            http_response_code(500);
            exit();
        }
    } else {
        error_log("Failed to write setting in .htaccess file: $env.\n");
        http_response_code(500);
        exit();
    }
}

define('SYSTEM_FLAG', $env);
