<?php

/**
 * Site configuration, in two halves.
 *
 * The first block holds what the Site Settings dialog rewrites: those lines are
 * edited in place by the administrator, so they carry plain values.
 *
 * The second reads the environment, which keeps credentials and deployment
 * settings in .env rather than in this tracked file. The fallbacks cover a plain
 * PHP hosting install, where this file is edited directly.
 *
 * php-fpm clears the environment by default, so the project image sets
 * `clear_env = no` for getenv() to see those variables inside a container.
 */

function config_value(string $name, string $fallback): string
{
    $value = getenv($name);

    // A variable that is defined but empty is a deliberate choice, such as a
    // disabled password, rather than an absent setting.
    return $value === false ? $fallback : $value;
}

function config_int(string $name, int $fallback): int
{
    $value = getenv($name);
    if ($value === false || $value === '' || !is_numeric($value)) {
        return $fallback;
    }

    return (int)$value;
}

function config_flag(string $name, bool $fallback): bool
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        return $fallback;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $parsed === null ? $fallback : $parsed;
}

// --- Settings the administrator edits from the site ---------------------------
// The Site Settings dialog rewrites these lines in place, so they hold plain
// values rather than reading the environment: a variable would be overwritten by
// the first save and the deployment would look like it had stopped working.
define('DEFAULT_LANGUAGE', 'en'); // Available values: en, zh-CN
define('DEFAULT_WEB_THEME', 'dark'); // Available values: dark, light; visitors can override it in the browser
define('SITE_NAME_EN', 'CS2 Loadout Manager'); // English name and fallback
define('SITE_NAME_ZH_CN', 'CS2 饰品管理器'); // Simplified Chinese name
define('ENABLE_SKIN_FUSION', true); // Allow cross-weapon paint combinations
define('LOCK_STATTRAK_COUNT', false); // Make the StatTrak kill count read-only
define('SERVER_ADDRESS', ''); // "hostname or IP:port"; empty hides the Connect to Server button
define('SERVER_PASSWORD', ''); // Empty connects directly; a value copies the console command instead

// --- Settings the deployment owns --------------------------------------------
// Never touched by the dialog, so these keep reading the environment and stay in
// .env, out of this tracked file.
define('AUTH_RATE_LIMIT_ATTEMPTS', config_int('AUTH_RATE_LIMIT_ATTEMPTS', 5)); // Failed attempts allowed within the time window
define('AUTH_RATE_LIMIT_WINDOW_SECONDS', config_int('AUTH_RATE_LIMIT_WINDOW_SECONDS', 1800)); // Failure tracking window in seconds
define('AUTH_RATE_LIMIT_LOCK_SECONDS', config_int('AUTH_RATE_LIMIT_LOCK_SECONDS', 300)); // Lock duration in seconds

define('SITE_ACCESS_PASSWORD', config_value('SITE_ACCESS_PASSWORD', '')); // Set a password to enable access protection
define('ADMIN_PASSWORD', config_value('ADMIN_PASSWORD', '')); // Set a password to enable administrator mode

define('DB_HOST', config_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', config_value('DB_PORT', '3306'));
define('DB_NAME', config_value('DB_NAME', 'your_db_name'));
define('DB_USER', config_value('DB_USER', 'your_db_user'));
define('DB_PASS', config_value('DB_PASS', 'your_db_password'));
