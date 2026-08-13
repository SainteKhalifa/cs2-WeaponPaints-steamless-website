<?php

	if ($postAction === 'save_keychain_choice') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		$defindex = (int)($_POST['weapon_defindex'] ?? 0);
		$keychainId = (int)($_POST['keychain_id'] ?? 0);
		$keychains = keychainsFromJson();
		$fallbackUrl = "index.php?action=edit&id={$id}&team={$team}";
		if (!$preset || !canEditPreset($preset) || !supportsWeaponCustomization($defindex) || !array_key_exists($keychainId, $keychains)) {
			stickerSlotResponse(false, ['message' => t('keychain_save_failed')], $fallbackUrl);
		}

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
			$currentParts = keychainValueParts($current['weapon_keychain'] ?? '');
			$newValue = $keychainId > 0 && $currentParts['id'] === $keychainId
				? (string)$current['weapon_keychain']
				: buildKeychainValue($keychainId);
			$db->query("UPDATE `wp_player_skins` SET `weapon_keychain` = :keychain_value
				WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
				"keychain_value" => $newValue,
				"steamid" => $preset['steamid'],
				"weapon_defindex" => $defindex,
				"team" => $targetTeam,
			]);
			$current['weapon_keychain'] = $newValue;
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
			stickerSlotResponse(false, ['message' => t('keychain_save_failed')], $fallbackUrl);
		}
		stickerSlotResponse(true, [
			'value' => $responseValue,
			'keychain_id' => $keychainId,
			'params' => keychainValueParts($responseValue),
		], $fallbackUrl);
	}
