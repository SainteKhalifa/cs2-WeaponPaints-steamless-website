<?php

	if ($postAction === 'create_preset') {
		$steamid = cleanSteamId($_POST['steamid'] ?? '');
		$nickname = trim((string)($_POST['nickname'] ?? ''));
		$enableLoadoutPassword = isset($_POST['enable_loadout_password']);
		$newLoadoutPassword = (string)($_POST['loadout_password'] ?? '');
		if (!isValidSteamId($steamid)) {
			$error = t('invalid_steamid');
			$action = 'new';
		} elseif (textLength($nickname) > 100) {
			$error = t('nickname_too_long');
			$action = 'new';
		} elseif ($enableLoadoutPassword && $newLoadoutPassword === '') {
			$error = t('loadout_password_required');
			$action = 'new';
		} else {
			$existingPreset = findPreset($db, $presetTable, $steamid);
			if ($existingPreset) {
				if (!canEditPreset($existingPreset)) {
					go('index.php?action=list&loadout_password_required=' . rawurlencode($steamid));
				}
				$loadoutPasswordHash = $enableLoadoutPassword ? password_hash($newLoadoutPassword, PASSWORD_DEFAULT) : null;
				$db->query("UPDATE `{$presetTable}` SET `nickname` = :nickname, `loadout_password_hash` = :loadout_password_hash WHERE `steamid` = :steamid", [
					"steamid" => $steamid,
					"nickname" => $nickname !== '' ? $nickname : null,
					"loadout_password_hash" => $loadoutPasswordHash,
				]);
				if ($enableLoadoutPassword && !isAdmin()) {
					$existingPreset['loadout_password_hash'] = $loadoutPasswordHash;
					markLoadoutPasswordVerified($existingPreset);
				} else {
					clearLoadoutPasswordVerification($existingPreset);
				}
				go('index.php?action=list&notice=updated_existing');
			}
			$loadoutPasswordHash = $enableLoadoutPassword ? password_hash($newLoadoutPassword, PASSWORD_DEFAULT) : null;
			$db->query("INSERT INTO `{$presetTable}` (`steamid`, `nickname`, `loadout_password_hash`) VALUES (:steamid, :nickname, :loadout_password_hash)", [
				"steamid" => $steamid,
				"nickname" => $nickname !== '' ? $nickname : null,
				"loadout_password_hash" => $loadoutPasswordHash,
			]);
			if ($enableLoadoutPassword && !isAdmin()) {
				$createdPreset = findPreset($db, $presetTable, $steamid);
				if ($createdPreset) {
					markLoadoutPasswordVerified($createdPreset);
				}
			}
			go('index.php?action=list');
		}
	}

	if ($postAction === 'delete_preset') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$preset = findPreset($db, $presetTable, $id);
		if (canDeletePreset($preset)) {
			$steamid = $preset['steamid'];
			$db->beginTransaction();
			try {
				foreach (['wp_player_skins', 'wp_player_knife', 'wp_player_agents', 'wp_player_gloves', 'wp_player_music', 'wp_player_pins'] as $table) {
					if (tableExists($db, $table)) {
						$db->query("DELETE FROM `{$table}` WHERE `steamid` = :steamid", ["steamid" => $steamid]);
					}
				}
				$db->query("DELETE FROM `{$skinSettingsTable}` WHERE `steamid` = :steamid", ["steamid" => $steamid]);
				$db->query("DELETE FROM `{$presetTable}` WHERE `steamid` = :steamid", ["steamid" => $steamid]);
				$db->commit();
			} catch (Throwable $exception) {
				if ($db->inTransaction()) {
					$db->rollBack();
				}
				throw $exception;
			}
		}
		go('index.php?action=list');
	}

	if ($postAction === 'save_identity') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		$steamid = cleanSteamId($_POST['steamid'] ?? '');
		$nickname = trim((string)($_POST['nickname'] ?? ''));
		$enableLoadoutPassword = isset($_POST['enable_loadout_password']);
		$newLoadoutPassword = (string)($_POST['loadout_password'] ?? '');

		if (!$preset || !canEditPreset($preset)) {
			go('index.php?action=list');
		}
		if (!isValidSteamId($steamid)) {
			go("index.php?action=edit&id={$id}&team={$team}&error=identity");
		}
		if (textLength($nickname) > 100) {
			go("index.php?action=edit&id={$id}&team={$team}&error=nickname");
		}

		$loadoutPasswordHash = null;
		if ($enableLoadoutPassword) {
			if ($newLoadoutPassword !== '') {
				$loadoutPasswordHash = password_hash($newLoadoutPassword, PASSWORD_DEFAULT);
			} elseif (loadoutHasPassword($preset)) {
				$loadoutPasswordHash = $preset['loadout_password_hash'];
			} else {
				go("index.php?action=edit&id={$id}&team={$team}&error=loadout_password");
			}
		}

		$duplicate = $db->select("SELECT `id` FROM `{$presetTable}` WHERE `steamid` = :steamid AND `id` <> :id LIMIT 1", [
			"steamid" => $steamid,
			"id" => $preset['id'] ?? 0,
		]);
		if ($duplicate) {
			go("index.php?action=edit&id={$id}&team={$team}&error=identity");
		}

		$oldSteamid = $preset['steamid'];
		if ($oldSteamid !== $steamid && steamIdHasDataInTables($db, $steamid, [
			'wp_player_skins',
			'wp_player_knife',
			'wp_player_agents',
			'wp_player_gloves',
			'wp_player_music',
			'wp_player_pins',
			$skinSettingsTable,
		])) {
			go("index.php?action=edit&id={$id}&team={$team}&error=steamid_data");
		}
		$db->beginTransaction();
		try {
			$db->query("UPDATE `{$presetTable}` SET `steamid` = :steamid, `nickname` = :nickname, `loadout_password_hash` = :loadout_password_hash WHERE `steamid` = :old_steamid", [
				"steamid" => $steamid,
				"nickname" => $nickname !== '' ? $nickname : null,
				"loadout_password_hash" => $loadoutPasswordHash,
				"old_steamid" => $oldSteamid,
			]);

			if ($oldSteamid !== $steamid) {
				foreach (['wp_player_skins', 'wp_player_knife', 'wp_player_agents', 'wp_player_gloves', 'wp_player_music', 'wp_player_pins'] as $table) {
					if (tableExists($db, $table)) {
						$db->query("UPDATE `{$table}` SET `steamid` = :new_steamid WHERE `steamid` = :old_steamid", [
							"new_steamid" => $steamid,
							"old_steamid" => $oldSteamid,
						]);
					}
				}
				$db->query("UPDATE `{$skinSettingsTable}` SET `steamid` = :new_steamid WHERE `steamid` = :old_steamid", [
					"new_steamid" => $steamid,
					"old_steamid" => $oldSteamid,
				]);
			}
			$db->commit();
		} catch (Throwable $exception) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $exception;
		}

		$updatedPreset = $preset;
		$updatedPreset['steamid'] = $steamid;
		$updatedPreset['loadout_password_hash'] = $loadoutPasswordHash;
		if ($enableLoadoutPassword && !isAdmin()) {
			markLoadoutPasswordVerified($updatedPreset);
		} else {
			clearLoadoutPasswordVerification($preset);
		}
		go("index.php?action=edit&id={$steamid}&team={$team}&saved=1");
	}
