<?php

/**
 * Persistence for the settings the administrator edits from the site.
 *
 * Upstream writes them straight into config.php. That file is tracked by git, so
 * every save turned into a local modification: the next `git pull` refused to
 * run, and the CS2 server password ended up in clear inside a tracked file.
 *
 * They are stored in a JSON file outside the repository instead. Nothing git
 * knows about ever changes, and the file survives an image rebuild as long as it
 * sits on a volume. `config.php` reads it before defining its constants.
 */

/**
 * Where the settings are kept.
 *
 * The default lives outside the web root and outside the repository. A
 * deployment that mounts something else points SITE_SETTINGS_FILE at it.
 */
function siteSettingsStorePath(): string
{
    $path = getenv('SITE_SETTINGS_FILE');
    if (is_string($path) && trim($path) !== '') {
        return trim($path);
    }

    return '/var/www/settings/site-settings.json';
}

/**
 * Reads the stored settings, or an empty array when nothing was ever saved.
 *
 * A missing or unreadable file is the normal first-run state, not an error: the
 * environment and the defaults in config.php take over.
 */
function loadStoredSiteSettings(): array
{
    $path = siteSettingsStorePath();
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $decoded = json_decode((string)file_get_contents($path), true);
    if (!is_array($decoded)) {
        error_log("Ignoring malformed site settings in {$path}");
        return [];
    }

    $settings = [];
    foreach ($decoded as $key => $value) {
        if (is_string($key) && (is_string($value) || is_bool($value) || is_int($value))) {
            $settings[$key] = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
        }
    }

    return $settings;
}

/**
 * Writes the settings, replacing whatever was there.
 *
 * Written to a temporary file and renamed, so a reader never sees a half-written
 * file, and locked so two administrators saving at once cannot interleave.
 *
 * @throws RuntimeException when the directory or the file cannot be written.
 */
function storeSiteSettings(array $settings): void
{
    $path = siteSettingsStorePath();
    $directory = dirname($path);

    if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException("Unable to create the settings directory {$directory}");
    }
    if (!is_writable($directory)) {
        throw new RuntimeException("The settings directory {$directory} is not writable");
    }
    if (is_file($path) && !is_writable($path)) {
        throw new RuntimeException("The settings file {$path} is not writable");
    }

    $encoded = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Unable to encode the site settings');
    }

    $lockHandle = fopen($directory . DIRECTORY_SEPARATOR . '.site-settings.lock', 'c');
    if ($lockHandle === false || !flock($lockHandle, LOCK_EX)) {
        if (is_resource($lockHandle)) {
            fclose($lockHandle);
        }
        throw new RuntimeException('Unable to lock the site settings for writing');
    }

    try {
        $temporaryPath = tempnam($directory, '.settings-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create a temporary settings file');
        }
        if (file_put_contents($temporaryPath, $encoded, LOCK_EX) !== strlen($encoded)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to write the temporary settings file');
        }

        // The password is in there, so it is never world-readable.
        @chmod($temporaryPath, 0640);
        if (!rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new RuntimeException("Unable to replace {$path}");
        }
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

/**
 * One stored setting, with the value config.php would otherwise have used.
 */
function storedSiteSetting(string $key, string $fallback): string
{
    static $stored = null;
    if ($stored === null) {
        $stored = loadStoredSiteSettings();
    }

    return array_key_exists($key, $stored) ? $stored[$key] : $fallback;
}
