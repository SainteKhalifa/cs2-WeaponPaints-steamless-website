<?php

function saveSkinSettingCache($db, $skinSettingsTable, $steamid, $team, $defindex, $paint, $wear, $seed, $stattrak, $stattrakCount, $nameTag, $stickerValues, $keychainValue)
{
	$stattrakCount = max(0, min(999999, (int)$stattrakCount));
	$stickerValues = is_array($stickerValues) ? array_values($stickerValues) : defaultStickerValues();
	$stickerValues = array_pad(array_slice($stickerValues, 0, 5), 5, defaultStickerValue());
	$keychainValue = (string)($keychainValue ?? defaultKeychainValue());
	if ($keychainValue === '') {
		$keychainValue = defaultKeychainValue();
	}
	$db->query("INSERT INTO `{$skinSettingsTable}`
		(`steamid`, `weapon_team`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain`)
		VALUES (:steamid, :team, :defindex, :paint, :wear, :seed, :stattrak, :stattrak_count, :nametag, :sticker_0, :sticker_1, :sticker_2, :sticker_3, :sticker_4, :keychain)
		ON DUPLICATE KEY UPDATE
			`weapon_wear` = :wear_update,
			`weapon_seed` = :seed_update,
			`weapon_stattrak` = :stattrak_update,
			`weapon_stattrak_count` = :stattrak_count_update,
			`weapon_nametag` = :nametag_update,
			`weapon_sticker_0` = :sticker_0_update,
			`weapon_sticker_1` = :sticker_1_update,
			`weapon_sticker_2` = :sticker_2_update,
			`weapon_sticker_3` = :sticker_3_update,
			`weapon_sticker_4` = :sticker_4_update,
			`weapon_keychain` = :keychain_update", [
		"steamid" => $steamid,
		"team" => $team,
		"defindex" => $defindex,
		"paint" => $paint,
		"wear" => $wear,
		"seed" => $seed,
		"stattrak" => $stattrak,
		"stattrak_count" => $stattrakCount,
		"nametag" => $nameTag,
		"sticker_0" => $stickerValues[0],
		"sticker_1" => $stickerValues[1],
		"sticker_2" => $stickerValues[2],
		"sticker_3" => $stickerValues[3],
		"sticker_4" => $stickerValues[4],
		"keychain" => $keychainValue,
		"wear_update" => $wear,
		"seed_update" => $seed,
		"stattrak_update" => $stattrak,
		"stattrak_count_update" => $stattrakCount,
		"nametag_update" => $nameTag,
		"sticker_0_update" => $stickerValues[0],
		"sticker_1_update" => $stickerValues[1],
		"sticker_2_update" => $stickerValues[2],
		"sticker_3_update" => $stickerValues[3],
		"sticker_4_update" => $stickerValues[4],
		"keychain_update" => $keychainValue,
	]);
}

function saveSkinRowSettingCache($db, $skinSettingsTable, $steamid, $team, $row)
{
	if (!$row || !isset($row['weapon_defindex'], $row['weapon_paint_id'])) {
		return;
	}
	saveSkinSettingCache(
		$db,
		$skinSettingsTable,
		$steamid,
		$team,
		(int)$row['weapon_defindex'],
		(int)$row['weapon_paint_id'],
		(float)($row['weapon_wear'] ?? 0),
		(int)($row['weapon_seed'] ?? 0),
		(int)($row['weapon_stattrak'] ?? 0),
		(int)($row['weapon_stattrak_count'] ?? 0),
		$row['weapon_nametag'] ?? null,
		stickerValuesFromRow($row),
		$row['weapon_keychain'] ?? defaultKeychainValue()
	);
}

function loadSkinSettingCache($db, $skinSettingsTable, $steamid, $team, $defindex, $paint)
{
	$rows = $db->select("SELECT `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain`
		FROM `{$skinSettingsTable}`
		WHERE `steamid` = :steamid AND `weapon_team` = :team AND `weapon_defindex` = :defindex AND `weapon_paint_id` = :paint
		LIMIT 1", [
		"steamid" => $steamid,
		"team" => $team,
		"defindex" => $defindex,
		"paint" => $paint,
	]);
	return $rows[0] ?? null;
}

function markLastSelectedSkinCache($db, $skinSettingsTable, $steamid, $team, $defindex, $paint)
{
	$db->query("UPDATE `{$skinSettingsTable}`
		SET `is_last_selected` = CASE WHEN `weapon_paint_id` = :paint THEN 1 ELSE 0 END
		WHERE `steamid` = :steamid AND `weapon_team` = :team AND `weapon_defindex` = :defindex", [
		"steamid" => $steamid,
		"team" => $team,
		"defindex" => $defindex,
		"paint" => $paint,
	]);
}

function loadLastSelectedSkinCache($db, $skinSettingsTable, $steamid, $team, $defindex)
{
	$rows = $db->select("SELECT `weapon_paint_id`, `weapon_wear`, `weapon_seed`
		FROM `{$skinSettingsTable}`
		WHERE `steamid` = :steamid AND `weapon_team` = :team AND `weapon_defindex` = :defindex AND `is_last_selected` = 1
		LIMIT 1", [
		"steamid" => $steamid,
		"team" => $team,
		"defindex" => $defindex,
	]);
	return $rows[0] ?? null;
}
