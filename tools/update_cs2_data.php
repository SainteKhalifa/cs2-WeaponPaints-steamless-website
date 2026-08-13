<?php
/**
 * Update CS2 data from ByMykel CSGO-API.
 *
 * Run from CLI:
 *   php tools/update_cs2_data.php
 * This clears the relevant source caches and downloads fresh data.
 *
 * Preview without writing files:
 *   php tools/update_cs2_data.php --dry-run
 *
 * Update only skins/gloves:
 *   php tools/update_cs2_data.php --only=skins
 *
 * Resume an interrupted update with valid cached sources:
 *   php tools/update_cs2_data.php --resume
 *
 * This script backs up existing files, then rewrites:
 *   data/skins_zh-CN.json / data/skins_en.json
 *   data/paint_kits_zh-CN.json / data/paint_kits_en.json
 *   data/gloves_zh-CN.json / data/gloves_en.json
 *   data/agents_zh-CN.json / data/agents_en.json
 *   data/music_zh-CN.json / data/music_en.json
 *   data/stickers_zh-CN.json / data/stickers_en.json
 *   data/keychains_zh-CN.json / data/keychains_en.json
 *   data/collectibles_zh-CN.json / data/collectibles_en.json
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run from the command line.\n";
    exit(1);
}

ini_set('memory_limit', '1024M');

$rootDir = dirname(__DIR__);
$dataDir = $rootDir . DIRECTORY_SEPARATOR . 'data';
$backupDir = $dataDir . DIRECTORY_SEPARATOR . 'backups';
$sourceCacheDir = $dataDir . DIRECTORY_SEPARATOR . '.source_cache';

$sources = [
    'zh-CN' => [
        'skins' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/zh-CN/skins.json',
        'agents' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/zh-CN/agents.json',
        'music' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/zh-CN/music_kits.json',
        'stickers' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/zh-CN/stickers.json',
        'keychains' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/zh-CN/keychains.json',
        'collectibles' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/zh-CN/collectibles.json',
    ],
    'en' => [
        'skins' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/en/skins.json',
        'agents' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/en/agents.json',
        'music' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/en/music_kits.json',
        'stickers' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/en/stickers.json',
        'keychains' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/en/keychains.json',
        'collectibles' => 'https://raw.githubusercontent.com/ByMykel/CSGO-API/refs/heads/main/public/api/en/collectibles.json',
    ],
];

$targets = [
    'zh-CN' => [
        'skins' => $dataDir . DIRECTORY_SEPARATOR . 'skins_zh-CN.json',
        'paint_kits' => $dataDir . DIRECTORY_SEPARATOR . 'paint_kits_zh-CN.json',
        'gloves' => $dataDir . DIRECTORY_SEPARATOR . 'gloves_zh-CN.json',
        'agents' => $dataDir . DIRECTORY_SEPARATOR . 'agents_zh-CN.json',
        'music' => $dataDir . DIRECTORY_SEPARATOR . 'music_zh-CN.json',
        'stickers' => $dataDir . DIRECTORY_SEPARATOR . 'stickers_zh-CN.json',
        'keychains' => $dataDir . DIRECTORY_SEPARATOR . 'keychains_zh-CN.json',
        'collectibles' => $dataDir . DIRECTORY_SEPARATOR . 'collectibles_zh-CN.json',
        'inventory_prefix' => '使用库存 ',
        'default_glove_name' => '使用库存手套',
        'default_agent_name' => '使用库存探员',
    ],
    'en' => [
        'skins' => $dataDir . DIRECTORY_SEPARATOR . 'skins_en.json',
        'paint_kits' => $dataDir . DIRECTORY_SEPARATOR . 'paint_kits_en.json',
        'gloves' => $dataDir . DIRECTORY_SEPARATOR . 'gloves_en.json',
        'agents' => $dataDir . DIRECTORY_SEPARATOR . 'agents_en.json',
        'music' => $dataDir . DIRECTORY_SEPARATOR . 'music_en.json',
        'stickers' => $dataDir . DIRECTORY_SEPARATOR . 'stickers_en.json',
        'keychains' => $dataDir . DIRECTORY_SEPARATOR . 'keychains_en.json',
        'collectibles' => $dataDir . DIRECTORY_SEPARATOR . 'collectibles_en.json',
        'inventory_prefix' => 'Use inventory ',
        'default_glove_name' => 'Use inventory gloves',
        'default_agent_name' => 'Use inventory agent',
    ],
];

class GithubRawRateLimitException extends RuntimeException
{
}

function githubRawRateLimitMessage(string $url): string
{
    return "GitHub raw requests are too frequent (HTTP 429). Please retry later with --resume. URL: {$url}";
}

function decodeJsonString(string $raw, string $source): array
{
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        throw new RuntimeException("Invalid JSON from {$source}: " . json_last_error_msg());
    }
    return $json;
}

function readJsonCache(string $cachePath): ?array
{
    if (!is_file($cachePath)) {
        return null;
    }

    $raw = file_get_contents($cachePath);
    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

function writeJsonCache(string $cachePath, string $raw): void
{
    $dir = dirname($cachePath);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException("Failed to create source cache directory: {$dir}");
    }

    $tempPath = tempnam($dir, '.source-cache-');
    if ($tempPath === false) {
        throw new RuntimeException("Failed to create temporary source cache: {$cachePath}");
    }

    try {
        if (file_put_contents($tempPath, $raw, LOCK_EX) === false) {
            throw new RuntimeException("Failed to write temporary source cache: {$cachePath}");
        }
        decodeJsonString((string)file_get_contents($tempPath), $tempPath);
        $rollbackPath = '';
        if (is_file($cachePath)) {
            $rollbackPath = $cachePath . '.rollback-' . bin2hex(random_bytes(6));
            if (!rename($cachePath, $rollbackPath)) {
                throw new RuntimeException("Failed to prepare source cache for replacement: {$cachePath}");
            }
        }
        if (!rename($tempPath, $cachePath)) {
            if ($rollbackPath !== '' && is_file($rollbackPath)) {
                @rename($rollbackPath, $cachePath);
            }
            throw new RuntimeException("Failed to install source cache: {$cachePath}");
        }
        if ($rollbackPath !== '') {
            @unlink($rollbackPath);
        }
    } catch (Throwable $exception) {
        if (is_file($tempPath)) {
            @unlink($tempPath);
        }
        throw $exception;
    }
}

function sourceCachePath(string $cacheDir, string $language, string $kind): string
{
    $safeLanguage = preg_replace('/[^A-Za-z0-9_-]/', '_', $language);
    $safeKind = preg_replace('/[^A-Za-z0-9_-]/', '_', $kind);
    return $cacheDir . DIRECTORY_SEPARATOR . "{$safeLanguage}_{$safeKind}.json";
}

function clearSourceCaches(array $sources, string $cacheDir, ?string $only): int
{
    $cleared = 0;
    foreach ($sources as $language => $urls) {
        $kinds = $only === 'skins' ? ['skins'] : array_keys($urls);
        foreach ($kinds as $kind) {
            $cachePath = sourceCachePath($cacheDir, (string)$language, (string)$kind);
            if (!is_file($cachePath)) {
                continue;
            }
            if (!unlink($cachePath)) {
                throw new RuntimeException("Failed to clear source cache: {$cachePath}");
            }
            $cleared++;
        }
    }
    return $cleared;
}

function throttleGithubRawRequest(string $url): void
{
    static $lastRequestAt = null;
    if (!str_contains($url, 'raw.githubusercontent.com')) {
        return;
    }

    if ($lastRequestAt !== null) {
        $elapsed = microtime(true) - $lastRequestAt;
        // GitHub raw can rate-limit short bursts aggressively, especially on shared networks.
        $delay = random_int(1500000, 3000000) / 1000000;
        if ($elapsed < $delay) {
            usleep((int)(($delay - $elapsed) * 1000000));
        }
    }
    $lastRequestAt = microtime(true);
}

function parseHttpStatusFromHeaders(array $headers): int
{
    $status = 0;
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/i', (string)$header, $matches)) {
            $status = (int)$matches[1];
        }
    }
    return $status;
}

function parseCommandHttpResponse(string $output): array
{
    $marker = '__HTTP_STATUS__:';
    $pos = strrpos($output, $marker);
    if ($pos === false) {
        return [$output, 0, trim($output)];
    }

    $body = substr($output, 0, $pos);
    $statusText = trim(substr($output, $pos + strlen($marker)));
    $status = (int)preg_replace('/\D.*/', '', $statusText);
    return [$body, $status, trim($output)];
}

function runCommand(array $command): array
{
    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        return [false, 'Failed to start command: ' . implode(' ', $command)];
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    $error = trim((string)$stderr . ($exitCode !== 0 ? "\nExit code: {$exitCode}" : ''));
    return [(string)$stdout, $error];
}

function runCurlExeDownload(string $url, bool $sslNoRevoke = false): array
{
    $command = [
        'curl.exe',
        '-L',
        '--silent',
        '--show-error',
        '--max-time',
        '90',
        '-A',
        'CS2-WeaponPaints-Updater/1.0',
        '--write-out',
        "\n__HTTP_STATUS__:%{http_code}",
    ];
    if ($sslNoRevoke) {
        // Windows curl.exe uses Schannel. --ssl-no-revoke is a fallback for CRYPT_E_REVOCATION_OFFLINE.
        $command[] = '--ssl-no-revoke';
    }
    $command[] = $url;

    [$output, $error] = runCommand($command);
    if ($output === false) {
        return [false, 0, $error];
    }

    [$body, $status, $fullOutput] = parseCommandHttpResponse($output);
    $message = trim($fullOutput . ($error !== '' ? "\n{$error}" : ''));
    return [$body, $status, $message];
}

function runPowerShellDownload(string $url): array
{
    $psUrl = str_replace("'", "''", $url);
    $script = "[Console]::OutputEncoding=[Text.UTF8Encoding]::new(); "
        . "try { \$r = Invoke-WebRequest -Uri '{$psUrl}' -UseBasicParsing -TimeoutSec 90; "
        . "Write-Output \$r.Content; Write-Output '__HTTP_STATUS__:'\$r.StatusCode } "
        . "catch { if (\$_.Exception.Response) { Write-Output '__HTTP_STATUS__:'([int]\$_.Exception.Response.StatusCode) }; Write-Error \$_.Exception.Message }";
    $command = 'powershell -NoProfile -ExecutionPolicy Bypass -Command ' . escapeshellarg($script) . ' 2>&1';
    $output = shell_exec($command);
    if (!is_string($output)) {
        return [false, 0, 'PowerShell did not return output'];
    }

    [$body, $status, $fullOutput] = parseCommandHttpResponse($output);
    return [$body, $status, $fullOutput];
}

function fetchJson(string $url, ?string $cachePath = null, bool $writeCache = true, bool $useCache = true): array
{
    if ($useCache && $cachePath !== null) {
        $cached = readJsonCache($cachePath);
        if ($cached !== null) {
            echo "Using cached source: {$cachePath}\n";
            return $cached;
        }
    }

    throttleGithubRawRequest($url);
    $raw = false;
    $errors = [];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_USERAGENT => 'CS2-WeaponPaints-Updater/1.0',
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        if ($status === 429) {
            throw new GithubRawRateLimitException(githubRawRateLimitMessage($url));
        }
        if ($raw === false || $status >= 400) {
            $errors[] = "URL {$url} - cURL: HTTP {$status}" . ($curlError !== '' ? " {$curlError}" : '');
            $raw = false;
        }
    } else {
        $errors[] = "URL {$url} - cURL: PHP cURL extension is not available";
    }

    if ($raw === false) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 90,
                'ignore_errors' => true,
                'header' => "User-Agent: CS2-WeaponPaints-Updater/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $previousError = error_get_last();
        $streamRaw = @file_get_contents($url, false, $context);
        $currentError = error_get_last();
        if (function_exists('http_get_last_response_headers')) {
            $headers = http_get_last_response_headers() ?: [];
        } else {
            $localVariables = get_defined_vars();
            $headers = isset($localVariables['http_response_header']) && is_array($localVariables['http_response_header'])
                ? $localVariables['http_response_header']
                : [];
        }
        $status = parseHttpStatusFromHeaders($headers);
        if ($status === 429) {
            throw new GithubRawRateLimitException(githubRawRateLimitMessage($url));
        }
        if ($streamRaw !== false && ($status === 0 || $status < 400)) {
            $raw = $streamRaw;
        } else {
            $message = ($currentError !== $previousError && isset($currentError['message'])) ? $currentError['message'] : 'unknown stream error';
            $errors[] = "URL {$url} - file_get_contents: HTTP {$status} {$message}";
        }
    }

    if ($raw === false && function_exists('shell_exec')) {
        [$body, $status, $output] = runCurlExeDownload($url, false);
        if ($status === 429) {
            throw new GithubRawRateLimitException(githubRawRateLimitMessage($url));
        }
        if (is_string($body) && $body !== '' && $status >= 200 && $status < 400) {
            $raw = $body;
        } else {
            $errors[] = "URL {$url} - curl.exe: HTTP {$status} {$output}";

            [$body, $status, $output] = runCurlExeDownload($url, true);
            if ($status === 429) {
                throw new GithubRawRateLimitException(githubRawRateLimitMessage($url));
            }
            if (is_string($body) && $body !== '' && $status >= 200 && $status < 400) {
                $raw = $body;
            } else {
                $errors[] = "URL {$url} - curl.exe --ssl-no-revoke: HTTP {$status} {$output}";
            }
        }
    }

    if ($raw === false && function_exists('shell_exec')) {
        [$body, $status, $output] = runPowerShellDownload($url);
        if ($status === 429) {
            throw new GithubRawRateLimitException(githubRawRateLimitMessage($url));
        }
        if (is_string($body) && $body !== '' && $status >= 200 && $status < 400) {
            $raw = $body;
        } else {
            $errors[] = "URL {$url} - PowerShell Invoke-WebRequest: HTTP {$status} {$output}";
        }
    }

    if ($raw === false) {
        throw new RuntimeException("Failed to download remote JSON.\n" . implode("\n", $errors));
    }

    $json = decodeJsonString($raw, $url);
    if ($cachePath !== null && $writeCache) {
        writeJsonCache($cachePath, $raw);
    }

    return $json;
}
function readJsonFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $json = json_decode((string)file_get_contents($path), true);
    return is_array($json) ? $json : [];
}

function prepareJsonFile(string $path, array $data): array
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException("Failed to encode JSON for: {$path}");
    }

    $directory = dirname($path);
    if (!is_dir($directory)) {
        throw new RuntimeException("Target directory does not exist: {$directory}");
    }
    $tempPath = tempnam($directory, '.cs2-data-');
    if ($tempPath === false) {
        throw new RuntimeException("Failed to create a temporary file for: {$path}");
    }

    try {
        if (file_put_contents($tempPath, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException("Failed to write temporary JSON for: {$path}");
        }
        $verified = json_decode((string)file_get_contents($tempPath), true);
        if (!is_array($verified) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Temporary JSON validation failed for {$path}: " . json_last_error_msg());
        }
    } catch (Throwable $exception) {
        @unlink($tempPath);
        throw $exception;
    }

    return ['target' => $path, 'temp' => $tempPath];
}

function cleanupPreparedJsonFiles(array $preparedFiles): void
{
    foreach ($preparedFiles as $prepared) {
        $tempPath = (string)($prepared['temp'] ?? '');
        if ($tempPath !== '' && is_file($tempPath)) {
            @unlink($tempPath);
        }
    }
}

function commitPreparedJsonFiles(array $preparedFiles, string $backupDir, string $timestamp): void
{
    $rollbackFiles = [];
    $installedTargets = [];
    try {
        foreach ($preparedFiles as $prepared) {
            backupFile($prepared['target'], $backupDir, $timestamp);
        }

        foreach ($preparedFiles as $prepared) {
            $target = $prepared['target'];
            if (!is_file($target)) {
                continue;
            }
            $rollbackPath = $target . '.rollback-' . bin2hex(random_bytes(6));
            if (!rename($target, $rollbackPath)) {
                throw new RuntimeException("Failed to prepare existing file for replacement: {$target}");
            }
            $rollbackFiles[$target] = $rollbackPath;
        }

        foreach ($preparedFiles as $prepared) {
            if (!rename($prepared['temp'], $prepared['target'])) {
                throw new RuntimeException("Failed to install validated JSON: {$prepared['target']}");
            }
            $installedTargets[] = $prepared['target'];
        }

        foreach ($rollbackFiles as $rollbackPath) {
            @unlink($rollbackPath);
        }
    } catch (Throwable $exception) {
        foreach ($installedTargets as $target) {
            @unlink($target);
        }
        foreach ($rollbackFiles as $target => $rollbackPath) {
            if (is_file($rollbackPath)) {
                @rename($rollbackPath, $target);
            }
        }
        cleanupPreparedJsonFiles($preparedFiles);
        throw $exception;
    }
}

function backupFile(string $path, string $backupDir, string $timestamp): void
{
    if (!is_file($path)) {
        return;
    }

    if (!is_dir($backupDir) && !mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
        throw new RuntimeException("Failed to create backup directory: {$backupDir}");
    }

    $backupPath = $backupDir . DIRECTORY_SEPARATOR . basename($path, '.json') . ".{$timestamp}.json";
    if (!copy($path, $backupPath)) {
        throw new RuntimeException("Failed to back up {$path} to {$backupPath}");
    }
}

function weaponSortMap(): array
{
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'weapon_order.php';
    $order = is_file($path) ? require $path : [];
    if (!is_array($order)) {
        return [];
    }

    $order = array_values(array_filter(array_map('intval', $order), static fn($defindex) => $defindex > 0));
    return array_flip($order);
}

function weaponSortRank(int $defindex): int
{
    $order = weaponSortMap();
    return $order[$defindex] ?? (10000 + $defindex);
}

function knifeDefindexes(): array
{
    return [500, 503, 505, 506, 507, 508, 509, 512, 514, 515, 516, 517, 518, 519, 520, 521, 522, 523, 525, 526];
}

function isKnifeDefindex(int $defindex): bool
{
    return in_array($defindex, knifeDefindexes(), true);
}

function inventoryDefaultName(string $paintName, string $inventoryPrefix): string
{
    $name = preg_replace('/\s*\|\s*(Default|默认)\s*$/u', '', trim($paintName));
    $name = preg_replace('/^(?:(?:使用库存|Use inventory)\s*)+/u', '', trim($name));
    if ($name === '') {
        $name = trim($paintName);
        $name = preg_replace('/^(?:(?:使用库存|Use inventory)\s*)+/u', '', trim($name));
    }
    return $inventoryPrefix . $name;
}

function vanillaKnifeName(string $paintName, string $weaponName): string
{
    $name = preg_replace('/^(?:(?:\x{4F7F}\x{7528}\x{5E93}\x{5B58}|Use inventory)\s*)+/u', '', trim($paintName));
    if (str_contains($name, '|')) {
        $parts = array_map('trim', explode('|', $name, 2));
        if ($parts[0] !== '') {
            $name = $parts[0];
        }
    }
    if ($name === '' || preg_match('/^weapon_/i', $name)) {
        $name = $weaponName;
    }
    return $name;
}

function weaponDisplayName(string $paintName, string $weaponName): string
{
    $name = trim(explode('|', $paintName, 2)[0]);
    $name = preg_replace('/^(?:(?:使用库存|Use inventory)\s*)+/u', '', trim($name));
    return $name !== '' ? $name : $weaponName;
}

function existingDefaultSkinRows(string $path, string $inventoryPrefix): array
{
    $rows = [];
    foreach (readJsonFile($path) as $row) {
        if ((int)($row['paint'] ?? -1) === 0 && isset($row['weapon_defindex'], $row['weapon_name'])) {
            if (isKnifeDefindex((int)$row['weapon_defindex'])) {
                continue;
            }
            $row['paint_name'] = inventoryDefaultName((string)($row['paint_name'] ?? $row['weapon_name']), $inventoryPrefix);
            $rows[(int)$row['weapon_defindex'] . '-' . (string)$row['weapon_name']] = $row;
        }
    }
    uasort($rows, static function (array $a, array $b): int {
        return weaponSortRank((int)$a['weapon_defindex']) <=> weaponSortRank((int)$b['weapon_defindex']);
    });
    return array_values($rows);
}

function existingDefaultGloveRow(string $path, string $defaultName): array
{
    foreach (readJsonFile($path) as $row) {
        if ((int)($row['weapon_defindex'] ?? -1) === 0 && (int)($row['paint'] ?? -1) === 0) {
            $row['paint_name'] = $defaultName;
            return $row;
        }
    }

    return [
        'weapon_defindex' => 0,
        'paint' => 0,
        'image' => '',
        'paint_name' => $defaultName,
    ];
}

function defaultAgentRows(string $path, string $defaultName): array
{
    $defaults = [];
    foreach (readJsonFile($path) as $row) {
        if (in_array((string)($row['model'] ?? ''), ['', 'null'], true) && in_array((int)($row['team'] ?? 0), [2, 3], true)) {
            $row['agent_name'] = $defaultName;
            $defaults[(int)$row['team']] = $row;
        }
    }

    foreach ([2, 3] as $team) {
        if (!isset($defaults[$team])) {
            $defaults[$team] = [
                'team' => $team,
                'image' => '',
                'model' => '',
                'agent_name' => $defaultName,
            ];
        }
    }

    ksort($defaults);
    return array_values($defaults);
}

function isGlove(array $item): bool
{
    $categoryId = (string)($item['category']['id'] ?? '');
    $categoryName = (string)($item['category']['name'] ?? '');
    $weaponId = (string)($item['weapon']['id'] ?? '');
    $weaponDefindex = (int)($item['weapon']['weapon_id'] ?? 0);

    return $categoryId === 'sfui_invpanel_filter_gloves'
        || stripos($categoryName, 'glove') !== false
        || str_contains($categoryName, '手套')
        || str_contains($weaponId, 'glove')
        || in_array($weaponDefindex, [4725, 5027, 5030, 5031, 5032, 5033, 5034, 5035], true);
}

function normalizePaint($paint)
{
    return ctype_digit((string)$paint) ? (string)$paint : (int)$paint;
}

function numericIdFromItem(array $item)
{
    if (isset($item['def_index']) && ctype_digit((string)$item['def_index'])) {
        return (string)$item['def_index'];
    }

    if (isset($item['id']) && preg_match('/(\d+)$/', (string)$item['id'], $matches)) {
        return $matches[1];
    }

    return null;
}

function teamNumberFromAgent(array $item): int
{
    $teamId = strtolower((string)($item['team']['id'] ?? ''));
    $teamName = strtolower((string)($item['team']['name'] ?? ''));

    if ($teamId === 'terrorists' || $teamName === 't') {
        return 2;
    }

    if ($teamId === 'counter-terrorists' || $teamName === 'ct') {
        return 3;
    }

    return 0;
}

function normalizeAgentModel(string $model): string
{
    $model = str_replace('\\', '/', trim($model));
    $prefix = 'agents/models/';
    if (str_starts_with($model, $prefix)) {
        $model = substr($model, strlen($prefix));
    }
    if (str_ends_with($model, '.vmdl')) {
        $model = substr($model, 0, -5);
    }
    return $model;
}

function buildSkinAndGloveRows(array $items, array $defaultSkinRows, array $defaultGloveRow, string $inventoryPrefix): array
{
    $skins = [];
    $seenSkins = [];
    $weaponPairs = [];
    $defaultSkinKeys = [];
    $warnings = [];
    foreach ($defaultSkinRows as $row) {
        $skins[] = $row;
        $key = (int)$row['weapon_defindex'] . '-' . (string)$row['weapon_name'];
        $seenSkins[$key . '-' . (int)$row['paint']] = true;
        if ((int)$row['paint'] === 0) {
            $defaultSkinKeys[$key] = true;
        }
    }

    $gloves = [$defaultGloveRow];
    $seenGloves = [];
    $skipped = [];

    foreach ($items as $item) {
        $weaponDefindex = (int)($item['weapon']['weapon_id'] ?? 0);
        $weaponName = (string)($item['weapon']['id'] ?? '');
        $originalWeaponName = (string)($item['original']['name'] ?? '');
        $isVanillaKnife = isKnifeDefindex($weaponDefindex) && array_key_exists('pattern', $item) && $item['pattern'] === null;
        if ($isVanillaKnife && str_starts_with($originalWeaponName, 'weapon_')) {
            $weaponName = $originalWeaponName;
        }
        $paint = normalizePaint($item['paint_index'] ?? '');
        if ($isVanillaKnife) {
            $paint = 0;
        }
        $image = (string)($item['image'] ?? '');
        $name = (string)($item['name'] ?? '');

        if ($weaponDefindex <= 0 || $paint === '' || $name === '') {
            $skipped[] = $name !== '' ? $name : '(missing name)';
            continue;
        }

        if (isGlove($item)) {
            $key = $weaponDefindex . '-' . (int)$paint;
            if (isset($seenGloves[$key])) {
                continue;
            }
            $seenGloves[$key] = true;
            $gloves[] = [
                'weapon_defindex' => $weaponDefindex,
                'paint' => $paint,
                'image' => $image,
                'paint_name' => $name,
            ];
            continue;
        }

        if (!str_starts_with($weaponName, 'weapon_')) {
            $skipped[] = $name;
            continue;
        }

        $weaponKey = $weaponDefindex . '-' . $weaponName;
        if (!isset($weaponPairs[$weaponKey])) {
            $weaponPairs[$weaponKey] = [
                'weapon_defindex' => $weaponDefindex,
                'weapon_name' => $weaponName,
                'display_name' => weaponDisplayName($name, $weaponName),
            ];
        }
        if ((int)$paint === 0) {
            $defaultSkinKeys[$weaponKey] = true;
        }

        if ((int)$paint === 0 && isKnifeDefindex($weaponDefindex)) {
            $name = vanillaKnifeName($name, $weaponName);
        }

        if ($image === '' && (int)$paint !== 0) {
            $skipped[] = $name;
            continue;
        }

        $key = $weaponKey . '-' . (int)$paint;
        if (isset($seenSkins[$key])) {
            continue;
        }
        $seenSkins[$key] = true;
        $skins[] = [
            'weapon_defindex' => $weaponDefindex,
            'weapon_name' => $weaponName,
            'paint' => $paint,
            'image' => $image,
            'paint_name' => $name,
            'legacy_model' => (bool)($item['legacy_model'] ?? false),
        ];
    }

    foreach ($weaponPairs as $weaponKey => $weapon) {
        if (isset($defaultSkinKeys[$weaponKey])) {
            continue;
        }

        $generatedName = isKnifeDefindex((int)$weapon['weapon_defindex'])
            ? vanillaKnifeName($weapon['display_name'], $weapon['weapon_name'])
            : inventoryDefaultName($weapon['display_name'], $inventoryPrefix);

        $skins[] = [
            'weapon_defindex' => $weapon['weapon_defindex'],
            'weapon_name' => $weapon['weapon_name'],
            'paint' => 0,
            'image' => '',
            'paint_name' => $generatedName,
            'legacy_model' => false,
        ];
        $warnings[] = "[WARNING] {$weapon['weapon_name']} is missing paint=0; a default item was generated. Please manually confirm and update paint_name.";
    }

    usort($skins, static function (array $a, array $b): int {
        return [weaponSortRank((int)$a['weapon_defindex']), (int)$a['paint']] <=> [weaponSortRank((int)$b['weapon_defindex']), (int)$b['paint']];
    });
    usort($gloves, static function (array $a, array $b): int {
        return [(int)$a['weapon_defindex'], (int)$a['paint']] <=> [(int)$b['weapon_defindex'], (int)$b['paint']];
    });

    return [$skins, $gloves, $skipped, $warnings];
}

function buildPaintKitRows(array $items): array
{
    $rowsByPaint = [];
    $skipped = [];

    foreach ($items as $item) {
        if (isGlove($item)) {
            continue;
        }

        $paint = (int)($item['paint_index'] ?? 0);
        $pattern = $item['pattern'] ?? null;
        $name = is_array($pattern) ? trim((string)($pattern['name'] ?? '')) : '';
        $sourceName = trim((string)($item['name'] ?? ''));
        $sourceWeapon = trim((string)($item['weapon']['id'] ?? ''));
        $sourceDefindex = (int)($item['weapon']['weapon_id'] ?? 0);
        $image = trim((string)($item['image'] ?? ''));

        if ($paint <= 0 || !is_array($pattern)) {
            continue;
        }
        if ($name === '' || $sourceName === '' || !str_starts_with($sourceWeapon, 'weapon_') || $sourceDefindex <= 0 || $image === '') {
            $skipped[] = $sourceName !== '' ? $sourceName : "paint {$paint}";
            continue;
        }
        if (!isset($rowsByPaint[$paint])) {
            $rowsByPaint[$paint] = [
                'paint' => $paint,
                'name' => $name,
                'sources' => [],
                '_source_keys' => [],
            ];
        }

        $sourceKey = $sourceDefindex . '|' . $sourceWeapon . '|' . $sourceName . '|' . $image;
        if (isset($rowsByPaint[$paint]['_source_keys'][$sourceKey])) {
            continue;
        }
        $rowsByPaint[$paint]['_source_keys'][$sourceKey] = true;
        $rowsByPaint[$paint]['sources'][] = [
            'source_name' => $sourceName,
            'source_weapon' => $sourceWeapon,
            'source_defindex' => $sourceDefindex,
            'image' => $image,
        ];
    }

    $rows = [];
    foreach ($rowsByPaint as $row) {
        usort($row['sources'], static function (array $a, array $b): int {
            $rankOrder = weaponSortRank((int)$a['source_defindex']) <=> weaponSortRank((int)$b['source_defindex']);
            if ($rankOrder !== 0) {
                return $rankOrder;
            }
            $defindexOrder = (int)$a['source_defindex'] <=> (int)$b['source_defindex'];
            return $defindexOrder !== 0 ? $defindexOrder : strnatcasecmp((string)$a['source_name'], (string)$b['source_name']);
        });
        $representative = $row['sources'][0];
        $rows[] = [
            'paint' => $row['paint'],
            'name' => $row['name'],
            'source_name' => $representative['source_name'],
            'source_weapon' => $representative['source_weapon'],
            'source_defindex' => $representative['source_defindex'],
            'image' => $representative['image'],
            'source_count' => count($row['sources']),
            'sources' => $row['sources'],
        ];
    }
    usort($rows, static function (array $a, array $b): int {
        $nameOrder = strnatcasecmp((string)$a['name'], (string)$b['name']);
        return $nameOrder !== 0 ? $nameOrder : ((int)$a['paint'] <=> (int)$b['paint']);
    });

    return [$rows, $skipped];
}

function buildAgentRows(array $items, array $defaultRows): array
{
    $rows = $defaultRows;
    $seen = [];
    $skipped = [];

    foreach ($items as $item) {
        $team = teamNumberFromAgent($item);
        $image = (string)($item['image'] ?? '');
        $model = normalizeAgentModel((string)($item['model_player'] ?? ''));
        $name = (string)($item['name'] ?? '');

        if (!in_array($team, [2, 3], true) || $image === '' || $model === '' || $name === '') {
            $skipped[] = $name !== '' ? $name : '(missing name)';
            continue;
        }

        $key = $team . '-' . $model;
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $rows[] = [
            'team' => $team,
            'image' => $image,
            'model' => $model,
            'agent_name' => $name,
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        return [(int)$a['team'], (string)$a['model'] !== '' ? 1 : 0, (string)$a['agent_name']] <=> [(int)$b['team'], (string)$b['model'] !== '' ? 1 : 0, (string)$b['agent_name']];
    });

    return [$rows, $skipped];
}

function buildSimpleIdRows(array $items, string $nameField): array
{
    $rows = [];
    $seen = [];
    $skipped = [];

    foreach ($items as $item) {
        $id = numericIdFromItem($item);
        $name = (string)($item['name'] ?? '');
        $image = (string)($item['image'] ?? '');

        if ($id === null || $name === '' || $image === '') {
            $skipped[] = $name !== '' ? $name : '(missing name)';
            continue;
        }

        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $rows[] = [
            'id' => $id,
            $nameField => $name,
            'image' => $image,
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        return (int)$a['id'] <=> (int)$b['id'];
    });

    return [$rows, $skipped];
}

function buildCollectibleRows(array $items): array
{
    $allowedTypes = [
        'Pin',
        'Service Medal',
        'Operation Coin',
        'Pick\'Em Coin',
        'Old Pick\'Em Trophy',
        'Fantasy Trophy',
        'Tournament Finalist Trophy',
        'Premier Season Coin',
        'Map Contributor Coin',
        null,
    ];
    $allowedItems = [];
    $filtered = [];

    foreach ($items as $item) {
        if (!array_key_exists('type', $item) || !in_array($item['type'], $allowedTypes, true)) {
            $filtered[] = (string)($item['name'] ?? '(missing name)');
            continue;
        }
        $allowedItems[] = $item;
    }

    [$rows, $skipped] = buildSimpleIdRows($allowedItems, 'name');
    return [$rows, $skipped, $filtered];
}

function normalizeMusicKitName(string $name): string
{
    return trim(preg_replace('/^StatTrak™\s*/u', '', $name));
}

function simpleDedupKey(string $value): string
{
    $value = preg_replace('/\s+/u', ' ', trim($value));
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function buildMusicRows(array $items): array
{
    $rowsById = [];
    $metaById = [];
    $rows = [];
    $seenNames = [];
    $skipped = [];

    foreach ($items as $item) {
        $id = numericIdFromItem($item);
        $originalName = (string)($item['name'] ?? '');
        $name = normalizeMusicKitName($originalName);
        $image = (string)($item['image'] ?? '');

        if ($id === null || $name === '' || $image === '') {
            $skipped[] = $originalName !== '' ? $originalName : '(missing name)';
            continue;
        }

        $isStatTrak = $originalName !== $name;
        if (isset($rowsById[$id])) {
            if (($metaById[$id]['is_stattrak'] ?? false) && !$isStatTrak) {
                $rowsById[$id] = [
                    'id' => $id,
                    'name' => $name,
                    'image' => $image,
                ];
                $metaById[$id]['is_stattrak'] = false;
            }
            continue;
        }

        $rowsById[$id] = [
            'id' => $id,
            'name' => $name,
            'image' => $image,
        ];
        $metaById[$id] = [
            'is_stattrak' => $isStatTrak,
        ];
    }

    foreach ($rowsById as $id => $row) {
        $key = simpleDedupKey((string)$row['name']);
        $isStatTrak = $metaById[$id]['is_stattrak'] ?? false;
        if (isset($seenNames[$key])) {
            $existingIndex = $seenNames[$key]['index'];
            if (($seenNames[$key]['is_stattrak'] ?? false) && !$isStatTrak) {
                $rows[$existingIndex] = $row;
                $seenNames[$key]['is_stattrak'] = false;
            }
            continue;
        }

        $seenNames[$key] = [
            'index' => count($rows),
            'is_stattrak' => $isStatTrak,
        ];
        $rows[] = $row;
    }

    usort($rows, static function (array $a, array $b): int {
        return (int)$a['id'] <=> (int)$b['id'];
    });

    return [$rows, $skipped];
}

$dryRun = in_array('--dry-run', $argv ?? [], true);
$resume = in_array('--resume', $argv ?? [], true);
$only = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--only=')) {
        $only = substr($arg, 7);
    }
}
if ($only !== null && !in_array($only, ['skins'], true)) {
    throw new InvalidArgumentException("Unsupported --only value: {$only}");
}
$timestamp = date('Ymd-His');
$summary = [];
$preparedUpdateFiles = [];

try {
    if ($resume) {
        echo "Resume mode: valid source caches will be reused.\n";
    } elseif ($dryRun) {
        echo "Fresh download mode: source caches will not be changed during dry run.\n";
    } else {
        $clearedCaches = clearSourceCaches($sources, $sourceCacheDir, $only);
        echo "Fresh download mode: cleared {$clearedCaches} source cache file(s).\n";
    }

    foreach ($sources as $language => $urls) {
        $target = $targets[$language];

        echo "Downloading {$language} skins/gloves data...\n";
        $skinItems = fetchJson($urls['skins'], sourceCachePath($sourceCacheDir, $language, 'skins'), !$dryRun, $resume);
        [$skins, $gloves, $skinSkipped, $skinWarnings] = buildSkinAndGloveRows(
            $skinItems,
            existingDefaultSkinRows($target['skins'], $target['inventory_prefix']),
            existingDefaultGloveRow($target['gloves'], $target['default_glove_name']),
            $target['inventory_prefix']
        );
        foreach ($skinWarnings as $warning) {
            echo $warning . "\n";
        }
        [$paintKits, $paintKitSkipped] = buildPaintKitRows($skinItems);
        $skinsSourceCount = count($skinItems);
        unset($skinItems);

        $agentsSourceCount = $musicSourceCount = $stickerSourceCount = $keychainSourceCount = $collectiblesSourceCount = 0;
        $agents = $music = $stickers = $keychains = $collectibles = [];
        $collectiblesFiltered = [];
        $agentSkipped = $musicSkipped = $stickerSkipped = $keychainSkipped = $collectibleSkipped = [];

        if ($only !== 'skins') {
            echo "Downloading {$language} agents data...\n";
            $agentItems = fetchJson($urls['agents'], sourceCachePath($sourceCacheDir, $language, 'agents'), !$dryRun, $resume);
            [$agents, $agentSkipped] = buildAgentRows(
                $agentItems,
                defaultAgentRows($target['agents'], $target['default_agent_name'])
            );
            $agentsSourceCount = count($agentItems);
            unset($agentItems);

            echo "Downloading {$language} music data...\n";
            $musicItems = fetchJson($urls['music'], sourceCachePath($sourceCacheDir, $language, 'music'), !$dryRun, $resume);
            [$music, $musicSkipped] = buildMusicRows($musicItems);
            $musicSourceCount = count($musicItems);
            unset($musicItems);

            echo "Downloading {$language} stickers data...\n";
            $stickerItems = fetchJson($urls['stickers'], sourceCachePath($sourceCacheDir, $language, 'stickers'), !$dryRun, $resume);
            [$stickers, $stickerSkipped] = buildSimpleIdRows($stickerItems, 'name');
            $stickerSourceCount = count($stickerItems);
            unset($stickerItems);

            echo "Downloading {$language} keychains data...\n";
            $keychainItems = fetchJson($urls['keychains'], sourceCachePath($sourceCacheDir, $language, 'keychains'), !$dryRun, $resume);
            [$keychains, $keychainSkipped] = buildSimpleIdRows($keychainItems, 'name');
            $keychainSourceCount = count($keychainItems);
            unset($keychainItems);

            echo "Downloading {$language} collectibles data...\n";
            $collectibleItems = fetchJson($urls['collectibles'], sourceCachePath($sourceCacheDir, $language, 'collectibles'), !$dryRun, $resume);
            [$collectibles, $collectibleSkipped, $collectiblesFiltered] = buildCollectibleRows($collectibleItems);
            $collectiblesSourceCount = count($collectibleItems);
            unset($collectibleItems);
        }

        if (!$dryRun) {
			$outputData = [
				'skins' => $skins,
				'paint_kits' => $paintKits,
				'gloves' => $gloves,
			];
			if ($only !== 'skins') {
				$outputData += [
					'agents' => $agents,
					'music' => $music,
					'stickers' => $stickers,
					'keychains' => $keychains,
					'collectibles' => $collectibles,
				];
			}

			foreach ($outputData as $kind => $data) {
				$preparedUpdateFiles[] = prepareJsonFile($target[$kind], $data);
			}
        }

        $summary[] = [
            'language' => $language,
            'skins_source' => $skinsSourceCount,
            'agents_source' => $agentsSourceCount,
            'music_source' => $musicSourceCount,
            'stickers_source' => $stickerSourceCount,
            'keychains_source' => $keychainSourceCount,
            'collectibles_source' => $collectiblesSourceCount,
            'skins_written' => count($skins),
            'paint_kits_written' => count($paintKits),
            'gloves_written' => count($gloves),
            'agents_written' => count($agents),
            'music_written' => count($music),
            'stickers_written' => count($stickers),
            'keychains_written' => count($keychains),
            'collectibles_written' => count($collectibles),
            'collectibles_filtered' => count($collectiblesFiltered),
            'skipped' => count($skinSkipped) + count($paintKitSkipped) + count($agentSkipped) + count($musicSkipped) + count($stickerSkipped) + count($keychainSkipped) + count($collectibleSkipped),
        ];

        unset($skins, $paintKits, $gloves, $agents, $music, $stickers, $keychains, $collectibles, $skinSkipped, $paintKitSkipped, $agentSkipped, $musicSkipped, $stickerSkipped, $keychainSkipped, $collectibleSkipped, $collectiblesFiltered);
    }

	if (!$dryRun) {
		commitPreparedJsonFiles($preparedUpdateFiles, $backupDir, $timestamp);
		$preparedUpdateFiles = [];
	}

    echo $dryRun ? "\nDry run complete. No files were changed.\n" : "\nUpdate complete.\n";
    foreach ($summary as $row) {
        echo "{$row['language']}: skinsSource={$row['skins_source']}, agentsSource={$row['agents_source']}, musicSource={$row['music_source']}, stickersSource={$row['stickers_source']}, keychainsSource={$row['keychains_source']}, collectiblesSource={$row['collectibles_source']}, skins={$row['skins_written']}, paintKits={$row['paint_kits_written']}, gloves={$row['gloves_written']}, agents={$row['agents_written']}, music={$row['music_written']}, stickers={$row['stickers_written']}, keychains={$row['keychains_written']}, collectibles={$row['collectibles_written']}, collectiblesFiltered={$row['collectibles_filtered']}, skipped={$row['skipped']}\n";
    }
    if (!$dryRun) {
        echo "Backups: {$backupDir}\n";
    }
} catch (Throwable $e) {
	cleanupPreparedJsonFiles($preparedUpdateFiles);
    fwrite(STDERR, "Update failed: " . $e->getMessage() . "\n");
    exit(1);
}
