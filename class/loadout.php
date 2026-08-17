<?php

function ensurePresetTable($db, $presetTable)
{
	$db->query("CREATE TABLE IF NOT EXISTS `{$presetTable}` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`steamid` VARCHAR(18) NOT NULL,
		`nickname` VARCHAR(100) NULL,
		`loadout_password_hash` VARCHAR(255) NULL,
		`created_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY `uniq_steamid` (`steamid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	if (!columnExists($db, $presetTable, 'loadout_password_hash')) {
		$db->query("ALTER TABLE `{$presetTable}` ADD `loadout_password_hash` VARCHAR(255) NULL AFTER `nickname`");
	}
}

function ensureSkinSettingsTable($db, $skinSettingsTable)
{
	$db->query("CREATE TABLE IF NOT EXISTS `{$skinSettingsTable}` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`steamid` VARCHAR(18) NOT NULL,
		`weapon_team` INT NOT NULL,
		`weapon_defindex` INT NOT NULL,
		`weapon_paint_id` INT NOT NULL,
		`weapon_wear` FLOAT NOT NULL DEFAULT 0,
		`weapon_seed` INT NOT NULL DEFAULT 0,
		`weapon_stattrak` TINYINT(1) NOT NULL DEFAULT 0,
		`weapon_stattrak_count` INT NOT NULL DEFAULT 0,
		`weapon_nametag` VARCHAR(64) NULL,
		`weapon_sticker_0` VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0',
		`weapon_sticker_1` VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0',
		`weapon_sticker_2` VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0',
		`weapon_sticker_3` VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0',
		`weapon_sticker_4` VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0',
		`weapon_keychain` VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0',
		`is_last_selected` TINYINT(1) NOT NULL DEFAULT 0,
		`updated_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY `uniq_skin_setting` (`steamid`, `weapon_team`, `weapon_defindex`, `weapon_paint_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	if (!columnExists($db, $skinSettingsTable, 'weapon_stattrak_count')) {
		$db->query("ALTER TABLE `{$skinSettingsTable}` ADD `weapon_stattrak_count` INT NOT NULL DEFAULT 0 AFTER `weapon_stattrak`");
	}
	if (!columnExists($db, $skinSettingsTable, 'weapon_nametag')) {
		$db->query("ALTER TABLE `{$skinSettingsTable}` ADD `weapon_nametag` VARCHAR(64) NULL AFTER `weapon_stattrak_count`");
	}
	$cachedItemColumns = [
		'weapon_sticker_0' => "VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0' AFTER `weapon_nametag`",
		'weapon_sticker_1' => "VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0' AFTER `weapon_sticker_0`",
		'weapon_sticker_2' => "VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0' AFTER `weapon_sticker_1`",
		'weapon_sticker_3' => "VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0' AFTER `weapon_sticker_2`",
		'weapon_sticker_4' => "VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0;0;0' AFTER `weapon_sticker_3`",
		'weapon_keychain' => "VARCHAR(255) NOT NULL DEFAULT '0;0;0;0;0' AFTER `weapon_sticker_4`",
		'is_last_selected' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER `weapon_keychain`",
	];
	foreach ($cachedItemColumns as $column => $definition) {
		if (!columnExists($db, $skinSettingsTable, $column)) {
			$db->query("ALTER TABLE `{$skinSettingsTable}` ADD `{$column}` {$definition}");
		}
	}
}

function tableExists($db, $table)
{
	return count($db->select("SHOW TABLES LIKE :table_name", ["table_name" => $table])) > 0;
}

function steamIdHasDataInTables($db, $steamid, array $tables)
{
	foreach ($tables as $table) {
		if (!tableExists($db, $table)) {
			continue;
		}
		$rows = $db->select("SELECT 1 AS `found` FROM `{$table}` WHERE `steamid` = :steamid LIMIT 1", [
			'steamid' => $steamid,
		]);
		if ($rows) {
			return true;
		}
	}
	return false;
}

function columnExists($db, $table, $column)
{
	return count($db->select("SHOW COLUMNS FROM `{$table}` LIKE :column_name", ["column_name" => $column])) > 0;
}

function findPreset($db, $presetTable, $steamid)
{
	$rows = $db->select("SELECT * FROM `{$presetTable}` WHERE `steamid` = :steamid LIMIT 1", ["steamid" => $steamid]);
	return $rows[0] ?? null;
}

function presetLabel($preset)
{
	return $preset['nickname'] !== null && $preset['nickname'] !== '' ? $preset['nickname'] : $preset['steamid'];
}

function loadoutHasPassword($preset)
{
	return !empty($preset['loadout_password_hash']);
}

function adminPassword()
{
	return defined('ADMIN_PASSWORD') ? (string)ADMIN_PASSWORD : '';
}

function isAdmin()
{
	$password = adminPassword();
	return $password !== ''
		&& !empty($_SESSION['is_admin'])
		&& hash_equals(hash('sha256', $password), (string)($_SESSION['cs2_admin_key'] ?? ''));
}

function loadoutPasswordVerificationToken($preset)
{
	return loadoutHasPassword($preset) ? hash('sha256', (string)$preset['loadout_password_hash']) : '';
}

function isLoadoutPasswordVerified($preset)
{
	$presetId = (string)($preset['id'] ?? '');
	$verified = $_SESSION['cs2_verified_loadouts'][$presetId] ?? '';
	return $presetId !== '' && loadoutHasPassword($preset) && hash_equals(loadoutPasswordVerificationToken($preset), (string)$verified);
}

function markLoadoutPasswordVerified($preset)
{
	$_SESSION['cs2_verified_loadouts'][(string)$preset['id']] = loadoutPasswordVerificationToken($preset);
}

function clearLoadoutPasswordVerification($preset)
{
	unset($_SESSION['cs2_verified_loadouts'][(string)($preset['id'] ?? '')]);
}

function canEditPreset($preset)
{
	return $preset && (isAdmin() || !loadoutHasPassword($preset) || isLoadoutPasswordVerified($preset));
}

function canDeletePreset($preset)
{
	return $preset && isAdmin();
}

function editUrl($preset, $team = 1)
{
	return 'index.php?' . http_build_query([
		'action' => 'edit',
		'id' => $preset['steamid'],
		'team' => $team,
	]);
}

function safeReturnUrl($value, $fallback = 'index.php')
{
	$value = trim((string)$value);
	return preg_match('/^index\.php(?:\?[A-Za-z0-9_=&%+.-]*)?$/', $value) ? $value : $fallback;
}

function cleanSteamId($steamid)
{
	return trim((string)$steamid);
}

function isValidSteamId($steamid)
{
	return preg_match('/^\d{5,18}$/', (string)$steamid) === 1;
}

function textLength($value)
{
	if (function_exists('mb_strlen')) {
		return mb_strlen($value, 'UTF-8');
	}
	preg_match_all('/./us', $value, $matches);
	return count($matches[0]);
}

function readNameTagFromPost()
{
	if (!array_key_exists('nametag_present', $_POST)) {
		return null;
	}
	if (!array_key_exists('nametag_enabled', $_POST)) {
		return null;
	}
	$nameTag = trim((string)($_POST['weapon_nametag'] ?? ''));
	if ($nameTag === '' || textLength($nameTag) > 20) {
		return false;
	}
	return $nameTag;
}

