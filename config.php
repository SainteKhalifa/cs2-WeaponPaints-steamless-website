<?php

/**
 * Site configuration.
 *
 * Every setting can come from an environment variable, which makes it possible
 * to deploy through Docker and a .env file without writing a single secret into
 * a tracked file. The fallbacks below cover a plain PHP hosting install, where
 * this file is edited directly.
 *
 * Three layers, highest first: what the administrator saved from the Site
 * Settings dialog, then the environment, then the values written below.
 * Upstream saves the dialog into config.php itself, which would both fight the
 * environment and leave this tracked file modified; class/site_settings_store.php
 * keeps it outside the repository instead.
 *
 * php-fpm clears the environment by default, so the project image sets
 * `clear_env = no` for getenv() to see those variables inside a container.
 */

require_once __DIR__ . '/class/site_settings_store.php';

function config_value(string $name, string $fallback): string
{
    $value = getenv($name);

    // A variable that is defined but empty is a deliberate choice, such as a
    // disabled password, rather than an absent setting.
    return $value === false ? $fallback : $value;
}


/**
 * Same as config_value(), for settings an empty string cannot describe.
 *
 * A blank site name is never a deliberate choice, unlike a blank password, and
 * the compose file forwards these variables whether the deployment sets them
 * or not.
 */
function config_text(string $name, string $fallback): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $fallback : $value;
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

define('DEFAULT_LANGUAGE', storedSiteSetting('default_language', config_value('DEFAULT_LANGUAGE', 'en'))); // Available values: en, zh-CN
define('DEFAULT_WEB_THEME', storedSiteSetting('default_web_theme', config_value('DEFAULT_WEB_THEME', 'dark'))); // Available values: dark, light; visitors can override it in the browser
define('SITE_NAME_EN', storedSiteSetting('site_name_en', config_text('SITE_NAME_EN', 'CS2 Loadout Manager'))); // English name and fallback
define('SITE_NAME_ZH_CN', storedSiteSetting('site_name_zh_cn', config_text('SITE_NAME_ZH_CN', 'CS2 饰品管理器'))); // Simplified Chinese name
define('ENABLE_SKIN_FUSION', storedSiteSetting('enable_skin_fusion', config_flag('ENABLE_SKIN_FUSION', true) ? '1' : '0') === '1'); // Allow cross-weapon paint combinations

// Steam persona names and avatars on the loadout list. Turn this off to keep the
// list to Steam64 ids and never contact Steam at all.
define('SHOW_STEAM_PROFILES', config_flag('SHOW_STEAM_PROFILES', true));
// Optional. Without a key the public profile page is used, which answers about
// one player per request, so refreshes are rationed. A key lifts that: the Web
// API answers about a hundred at a time. https://steamcommunity.com/dev/apikey
define('STEAM_API_KEY', config_value('STEAM_API_KEY', ''));
// How long a name and avatar are trusted, in seconds. A day keeps the traffic
// negligible; the floor is 300 so a typo cannot turn every view into a call.
define('STEAM_PROFILE_TTL', config_int('STEAM_PROFILE_TTL', 86400));

// The Connect to Server button in the header. Empty address hides it; with a
// password the button copies the console command instead of launching the game.
define('SERVER_ADDRESS', storedSiteSetting('server_address', config_value('SERVER_ADDRESS', ''))); // "hostname or IP:port"
define('SERVER_PASSWORD', storedSiteSetting('server_password', config_value('SERVER_PASSWORD', ''))); // Leave empty to connect directly

// Players may set their own StatTrak kill count. The counter is game state the
// plugin increments on its own, so an editable field lets anyone claim a record
// they never earned: turn this on to make it read-only.
define('LOCK_STATTRAK_COUNT', storedSiteSetting('lock_stattrak_count', config_flag('LOCK_STATTRAK_COUNT', false) ? '1' : '0') === '1');

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
