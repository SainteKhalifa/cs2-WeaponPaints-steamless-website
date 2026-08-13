<?php

	if ($postAction === 'save_sticker_choice') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		$defindex = (int)($_POST['weapon_defindex'] ?? 0);
		$slot = (int)($_POST['sticker_slot'] ?? -1);
		$stickerId = (int)($_POST['sticker_id'] ?? 0);
		$stickers = stickersFromJson();
		$fallbackUrl = "index.php?action=edit&id={$id}&team={$team}";
		$slotCount = stickerSlotCount($defindex);
		if (!$preset || !canEditPreset($preset) || !supportsWeaponCustomization($defindex) || $slot < 0 || $slot >= min(5, $slotCount) || !array_key_exists($stickerId, $stickers)) {
			stickerSlotResponse(false, ['message' => t('sticker_save_failed')], $fallbackUrl);
		}

		$field = "weapon_sticker_{$slot}";
		$responseValue = null;
		$updated = false;
		$db->beginTransaction();
		try {
		foreach (writeTeams($team) as $targetTeam) {
			$rows = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain` FROM `wp_player_skins`
				WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
				"steamid" => $preset['steamid'],
				"weapon_defindex" => $defindex,
				"team" => $targetTeam,
			]);
			if (!$rows) {
				continue;
			}
			$current = $rows[0];
			$currentParts = stickerValueParts($current[$field] ?? '');
			$newValue = $stickerId > 0 && $currentParts['id'] === $stickerId
				? (string)$current[$field]
				: buildStickerValue($stickerId);
			$db->query("UPDATE `wp_player_skins` SET `{$field}` = :sticker_value
				WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
				"sticker_value" => $newValue,
				"steamid" => $preset['steamid'],
				"weapon_defindex" => $defindex,
				"team" => $targetTeam,
			]);
			$current[$field] = $newValue;
			saveSkinRowSettingCache($db, $skinSettingsTable, $preset['steamid'], $targetTeam, $current);
			if ($responseValue === null) {
				$responseValue = $newValue;
			}
			$updated = true;
		}
			$db->commit();
		} catch (Throwable $exception) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $exception;
		}

		if (!$updated) {
			stickerSlotResponse(false, ['message' => t('sticker_save_failed')], $fallbackUrl);
		}
		stickerSlotResponse(true, [
			'value' => $responseValue,
			'slot' => $slot,
			'sticker_id' => $stickerId,
			'params' => stickerValueParts($responseValue),
		], $fallbackUrl);
	}
	if ($postAction === 'save_sticker_slot') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		$defindex = (int)($_POST['weapon_defindex'] ?? 0);
		$slot = (int)($_POST['sticker_slot'] ?? -1);
		$fallbackUrl = "index.php?action=edit&id={$id}&team={$team}";
		$slotCount = stickerSlotCount($defindex);
		if (!$preset || !canEditPreset($preset) || !supportsWeaponCustomization($defindex) || $slot < 0 || $slot >= min(5, $slotCount)) {
			stickerSlotResponse(false, ['message' => t('sticker_save_failed')], $fallbackUrl);
		}

		$field = "weapon_sticker_{$slot}";
		$params = readStickerAdvancedParamsFromPost();
		$responseValue = null;
		$updated = false;
		$db->beginTransaction();
		try {
		foreach (writeTeams($team) as $targetTeam) {
			$rows = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain` FROM `wp_player_skins`
				WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
				"steamid" => $preset['steamid'],
				"weapon_defindex" => $defindex,
				"team" => $targetTeam,
			]);
			if (!$rows) {
				continue;
			}
			$parts = stickerValueParts($rows[0][$field] ?? '');
			if ($parts['id'] <= 0) {
				continue;
			}
			$newValue = buildStickerValueFromParts($parts['id'], $parts['schema'], $params);
			$db->query("UPDATE `wp_player_skins` SET `{$field}` = :sticker_value
				WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
				"sticker_value" => $newValue,
				"steamid" => $preset['steamid'],
				"weapon_defindex" => $defindex,
				"team" => $targetTeam,
			]);
			$current = $rows[0];
			$current[$field] = $newValue;
			saveSkinRowSettingCache($db, $skinSettingsTable, $preset['steamid'], $targetTeam, $current);
			if ($responseValue === null) {
				$responseValue = $newValue;
			}
			$updated = true;
		}
			$db->commit();
		} catch (Throwable $exception) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $exception;
		}

		if (!$updated) {
			stickerSlotResponse(false, ['message' => t('sticker_save_failed')], $fallbackUrl);
		}
		stickerSlotResponse(true, [
			'value' => $responseValue,
			'slot' => $slot,
			'params' => stickerValueParts($responseValue),
		], $fallbackUrl);
	}
