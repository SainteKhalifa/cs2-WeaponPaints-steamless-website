<?php

/**
 * Steam persona names and avatars for the loadout list.
 *
 * The list can hold dozens of players, so the naive approach — one request per
 * card, on every page view — would hammer Steam and get the server throttled or
 * blocked. Three rules keep the traffic negligible:
 *
 *   1. Everything is cached in `wp_steam_profiles`. A page view where nothing
 *      has expired makes no request at all.
 *   2. GetPlayerSummaries takes up to 100 ids per call, so a whole page of stale
 *      players is refreshed in a single request.
 *   3. A failed or fruitless lookup is remembered too. Without that, a private
 *      or deleted account would be retried on every single page view.
 *
 * The whole feature is off unless STEAM_API_KEY is set: no key, no requests, and
 * the list falls back to the id alone.
 */

/** Steam's documented ceiling for one GetPlayerSummaries call. */
const STEAM_PROFILES_PER_REQUEST = 100;

/** Never spend longer than this on Steam; the page must render regardless. */
const STEAM_PROFILES_TIMEOUT_SECONDS = 5;

/**
 * How long a cached profile is trusted, in seconds.
 *
 * A day is plenty: people rename themselves rarely, and this is a convenience
 * label, not something the site depends on.
 */
function steamProfileTtl(): int
{
	$ttl = defined('STEAM_PROFILE_TTL') ? (int)STEAM_PROFILE_TTL : 86400;

	// A tiny TTL would turn every page view into a request, which is the very
	// thing this file exists to avoid.
	return max(300, $ttl);
}

function steamApiKey(): string
{
	return defined('STEAM_API_KEY') ? trim((string)STEAM_API_KEY) : '';
}

function steamProfilesEnabled(): bool
{
	return steamApiKey() !== '';
}

function ensureSteamProfileTable($db): void
{
	static $ready = false;
	if ($ready) {
		return;
	}

	$db->query("CREATE TABLE IF NOT EXISTS `wp_steam_profiles` (
		`steamid` VARCHAR(18) NOT NULL,
		`persona_name` VARCHAR(64) NULL,
		`avatar_url` VARCHAR(255) NULL,
		`checked_at` INT UNSIGNED NOT NULL,
		PRIMARY KEY (`steamid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

	$ready = true;
}

/**
 * Persona name and avatar for each id, from the cache, refreshing what expired.
 *
 * @param array $steamids Steam64 ids, in any order, duplicates allowed.
 * @return array<string,array{name:string,avatar:string}> Keyed by Steam64 id;
 *         ids Steam knows nothing about are simply absent.
 */
function steamProfiles($db, array $steamids): array
{
	if (!steamProfilesEnabled() || !$steamids) {
		return [];
	}

	$wanted = [];
	foreach ($steamids as $steamid) {
		$steamid = trim((string)$steamid);
		if ($steamid !== '' && ctype_digit($steamid)) {
			$wanted[$steamid] = true;
		}
	}
	if (!$wanted) {
		return [];
	}
	$wanted = array_keys($wanted);

	ensureSteamProfileTable($db);

	$placeholders = [];
	$bindings = [];
	foreach ($wanted as $index => $steamid) {
		$placeholders[] = ":id{$index}";
		$bindings["id{$index}"] = $steamid;
	}

	$cached = [];
	$rows = $db->select(
		"SELECT `steamid`, `persona_name`, `avatar_url`, `checked_at` FROM `wp_steam_profiles`
		WHERE `steamid` IN (" . implode(',', $placeholders) . ")",
		$bindings
	);
	foreach ($rows as $row) {
		$cached[(string)$row['steamid']] = $row;
	}

	$now = time();
	$ttl = steamProfileTtl();
	$stale = [];
	foreach ($wanted as $steamid) {
		$checkedAt = (int)($cached[$steamid]['checked_at'] ?? 0);
		if ($now - $checkedAt >= $ttl) {
			$stale[] = $steamid;
		}
	}

	if ($stale) {
		// One request covers a hundred players, so a full page is a single call.
		foreach (array_chunk($stale, STEAM_PROFILES_PER_REQUEST) as $chunk) {
			$fetched = fetchSteamSummaries($chunk);
			if ($fetched === null) {
				// Steam is unreachable or refusing. Leave the cache alone so the
				// last known names keep showing, and try again on a later view.
				break;
			}
			foreach ($chunk as $steamid) {
				$profile = $fetched[$steamid] ?? ['name' => '', 'avatar' => ''];
				storeSteamProfile($db, $steamid, $profile['name'], $profile['avatar'], $now);
				$cached[$steamid] = [
					'steamid' => $steamid,
					'persona_name' => $profile['name'],
					'avatar_url' => $profile['avatar'],
					'checked_at' => $now,
				];
			}
		}
	}

	$profiles = [];
	foreach ($cached as $steamid => $row) {
		$name = trim((string)($row['persona_name'] ?? ''));
		$avatar = trim((string)($row['avatar_url'] ?? ''));
		if ($name === '' && $avatar === '') {
			continue;
		}
		$profiles[$steamid] = ['name' => $name, 'avatar' => $avatar];
	}

	return $profiles;
}

/**
 * Writes one profile, including an empty one.
 *
 * Storing the empty result is deliberate: it is what stops a private account
 * from being looked up again on every page view.
 */
function storeSteamProfile($db, string $steamid, string $name, string $avatar, int $checkedAt): void
{
	$db->query(
		"INSERT INTO `wp_steam_profiles` (`steamid`, `persona_name`, `avatar_url`, `checked_at`)
		VALUES (:steamid, :persona_name, :avatar_url, :checked_at)
		ON DUPLICATE KEY UPDATE
			`persona_name` = :persona_name_update,
			`avatar_url` = :avatar_url_update,
			`checked_at` = :checked_at_update",
		[
			'steamid' => $steamid,
			'persona_name' => $name,
			'avatar_url' => $avatar,
			'checked_at' => $checkedAt,
			'persona_name_update' => $name,
			'avatar_url_update' => $avatar,
			'checked_at_update' => $checkedAt,
		]
	);
}

/**
 * Asks Steam about one batch of ids.
 *
 * @return array<string,array{name:string,avatar:string}>|null Null when the call
 *         itself failed, which the caller treats differently from "Steam has
 *         nothing for this id".
 */
function fetchSteamSummaries(array $steamids): ?array
{
	if (!$steamids) {
		return null;
	}

	$url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?'
		. http_build_query([
			'key' => steamApiKey(),
			'steamids' => implode(',', $steamids),
		]);

	$body = function_exists('curl_init') ? steamRequestWithCurl($url) : steamRequestWithStream($url);
	if ($body === null) {
		return null;
	}

	$decoded = json_decode($body, true);
	if (!is_array($decoded) || !isset($decoded['response']['players']) || !is_array($decoded['response']['players'])) {
		error_log('Steam profile lookup returned an unexpected payload');
		return null;
	}

	$profiles = [];
	foreach ($decoded['response']['players'] as $player) {
		$steamid = trim((string)($player['steamid'] ?? ''));
		if ($steamid === '') {
			continue;
		}

		// medium is the 64px avatar, the size the list actually displays.
		$avatar = (string)($player['avatarmedium'] ?? $player['avatar'] ?? '');
		$profiles[$steamid] = [
			'name' => steamTrimName(trim((string)($player['personaname'] ?? ''))),
			'avatar' => strpos($avatar, 'https://') === 0 ? $avatar : '',
		];
	}

	return $profiles;
}

/**
 * The container ships the curl extension; this is the path it takes.
 */
function steamRequestWithCurl(string $url): ?string
{
	$handle = curl_init($url);
	curl_setopt_array($handle, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => STEAM_PROFILES_TIMEOUT_SECONDS,
		CURLOPT_CONNECTTIMEOUT => STEAM_PROFILES_TIMEOUT_SECONDS,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_USERAGENT => 'cs2-weaponpaints-website',
	]);
	$body = curl_exec($handle);
	$status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
	$error = curl_error($handle);
	curl_close($handle);

	if ($body === false || $status !== 200) {
		error_log("Steam profile lookup failed (HTTP {$status}) {$error}");
		return null;
	}

	return (string)$body;
}

/**
 * Fallback for a plain PHP host without the curl extension.
 *
 * Returns null when allow_url_fopen is off too, in which case the feature stays
 * inert rather than breaking the page.
 */
function steamRequestWithStream(string $url): ?string
{
	$context = stream_context_create([
		'http' => [
			'method' => 'GET',
			'timeout' => STEAM_PROFILES_TIMEOUT_SECONDS,
			'ignore_errors' => true,
			'user_agent' => 'cs2-weaponpaints-website',
		],
	]);

	$body = @file_get_contents($url, false, $context);
	if ($body === false) {
		error_log('Steam profile lookup failed: no curl extension and the stream request did not go through');
		return null;
	}

	// $http_response_header is populated by the stream wrapper itself.
	$status = 0;
	foreach ($http_response_header ?? [] as $header) {
		if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $match)) {
			$status = (int)$match[1];
		}
	}
	if ($status !== 200) {
		error_log("Steam profile lookup failed (HTTP {$status})");
		return null;
	}

	return (string)$body;
}

/**
 * Cuts a persona name to what the column holds, on whole characters.
 *
 * mbstring is in the project image but not on every PHP host, and cutting bytes
 * would leave a broken UTF-8 sequence at the end. class/inspect.php guards the
 * same way.
 */
function steamTrimName(string $name): string
{
	if (function_exists('mb_substr')) {
		return mb_substr($name, 0, 64);
	}

	return preg_match('/^.{0,64}/us', $name, $cut) === 1 ? $cut[0] : '';
}
