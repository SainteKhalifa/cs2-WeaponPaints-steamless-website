<?php

$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
	|| (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
if (session_status() !== PHP_SESSION_ACTIVE) {
	$sessionCookie = session_get_cookie_params();
	session_set_cookie_params([
		'lifetime' => (int)($sessionCookie['lifetime'] ?? 0),
		'path' => (string)($sessionCookie['path'] ?? '') !== '' ? (string)$sessionCookie['path'] : '/',
		'domain' => (string)($sessionCookie['domain'] ?? ''),
		'secure' => $isHttps,
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	session_start();
}

$presetTable = 'wp_presets';
$skinSettingsTable = 'wp_skin_settings_cache';
$availableLanguages = ['zh-CN' => '简体中文', 'en' => 'English'];
$languageCookieName = 'cs2_wp_lang';
$cookiePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$cookiePath = $cookiePath === '' ? '/' : $cookiePath . '/';
$requestedLanguage = $_GET['lang'] ?? $_COOKIE[$languageCookieName] ?? (defined('DEFAULT_LANGUAGE') ? DEFAULT_LANGUAGE : 'zh-CN');
$currentLanguage = array_key_exists($requestedLanguage, $availableLanguages) ? $requestedLanguage : 'zh-CN';
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $availableLanguages)) {
	setcookie($languageCookieName, $currentLanguage, [
		'expires' => time() + 60 * 60 * 24 * 365,
		'path' => $cookiePath,
		'secure' => $isHttps,
		'httponly' => false,
		'samesite' => 'Lax',
	]);
}
UtilsClass::setLanguage($currentLanguage);

$siteNames = [
	'en' => defined('SITE_NAME_EN') ? trim((string)SITE_NAME_EN) : '',
	'zh-CN' => defined('SITE_NAME_ZH_CN') ? trim((string)SITE_NAME_ZH_CN) : '',
];
$siteNameFallback = 'CS2 WeaponPaints Loadout Manager';
$siteName = $siteNames[$currentLanguage] !== ''
	? $siteNames[$currentLanguage]
	: ($siteNames['en'] !== '' ? $siteNames['en'] : $siteNameFallback);
$teams = $currentLanguage === 'en'
	? [2 => 'T', 3 => 'CT']
	: [2 => 'T 阵营', 3 => 'CT 阵营'];

