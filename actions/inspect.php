<?php

	if ($postAction === 'import_inspect_link') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		$defindex = (int)($_POST['weapon_defindex'] ?? 0);
		$fallbackUrl = "index.php?action=edit&id={$id}&team={$team}";
		$skins = UtilsClass::skinsFromJson();
		if (!$preset || !canEditPreset($preset) || $defindex <= 0 || !array_key_exists($defindex, $skins)) {
			go("{$fallbackUrl}&error=inspect_failed");
		}

		$decoded = InspectLink::decode((string)($_POST['inspect_link'] ?? ''));
		if (!$decoded['ok']) {
			go("{$fallbackUrl}&error=inspect_{$decoded['error']}");
		}

		// The link comes from the player, so everything is cross-checked against
		// the site data before it can reach the database.
		$sanitized = InspectLink::sanitize(
			$decoded['item'],
			inspectReference($defindex, $skins, stickersFromJson(), keychainsFromJson())
		);
		if (!$sanitized['ok']) {
			go("{$fallbackUrl}&error=inspect_{$sanitized['error']}");
		}

		$item = $sanitized['item'];
		$steamid = $preset['steamid'];
		$knifes = UtilsClass::getKnifeTypes();
		$gloves = glovesFromJson();

		// The plugin overwrites a sticker slot's base position as soon as an
		// offset is non-zero, which piles every sticker in the middle of the
		// weapon. Dropping the offsets restores the default layout, so that is
		// what we do unless the player explicitly asks to keep them.
		$keepPlacement = array_key_exists('inspect_keep_placement', $_POST);
		$stickerValues = defaultStickerValues();
		foreach ($item['stickers'] as $sticker) {
			if (!$keepPlacement) {
				$sticker['x'] = 0;
				$sticker['y'] = 0;
			}
			$stickerValues[(int)$sticker['slot']] = buildStickerValueFromParts($sticker['id'], $sticker['id'], $sticker);
		}
		$keychainValue = $item['keychain'] !== null
			? buildKeychainValueFromParts($item['keychain']['id'], [
				'x' => $item['keychain']['x'],
				'y' => $item['keychain']['y'],
				'z' => $item['keychain']['z'],
				'template' => $item['keychain']['seed'],
			])
			: defaultKeychainValue();

		$paint = (int)$item['paintindex'];
		$wear = round((float)$item['paintwear'], 8);
		$seed = (int)$item['paintseed'];
		$stattrak = $item['stattrak'] ? 1 : 0;
		$nameTag = $item['customname'] !== '' ? $item['customname'] : null;

		$isKnifeSkin = in_array($defindex, knifeDefindexes($knifes), true);
		$isGloveSkin = in_array($defindex, gloveDefindexes($gloves), true);

		$db->beginTransaction();
		try {
			foreach (writeTeams($team) as $targetTeam) {
				// Knives and gloves have their own selection table. The link can
				// only describe the piece already equipped, but the row may be
				// missing if the player has never saved anything yet.
				if ($isKnifeSkin && isset($knifes[$defindex])) {
					$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`)
						VALUES(:steamid, :knife, :team)
						ON DUPLICATE KEY UPDATE `knife` = :knife_update", [
						"steamid" => $steamid,
						"knife" => $knifes[$defindex]['weapon_name'],
						"team" => $targetTeam,
						"knife_update" => $knifes[$defindex]['weapon_name'],
					]);
				}
				if ($isGloveSkin && tableExists($db, 'wp_player_gloves')) {
					$db->query("INSERT INTO `wp_player_gloves` (`steamid`, `weapon_team`, `weapon_defindex`)
						VALUES (:steamid, :team, :weapon_defindex)
						ON DUPLICATE KEY UPDATE `weapon_defindex` = :weapon_defindex_update", [
						"steamid" => $steamid,
						"team" => $targetTeam,
						"weapon_defindex" => $defindex,
						"weapon_defindex_update" => $defindex,
					]);
				}

				$existing = $db->select("SELECT `weapon_defindex`, `weapon_stattrak_count` FROM `wp_player_skins`
					WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
					"steamid" => $steamid,
					"weapon_defindex" => $defindex,
					"team" => $targetTeam,
				]);

				// An inspect link carries a kill count of its own. It is treated
				// like any other player-supplied value: taken when editing is
				// allowed, otherwise the stored counter is carried over.
				$stattrakCount = resolveStatTrakCount($existing[0] ?? null, (int)$item['stattrak_count']);

				$bindings = [
					"steamid" => $steamid,
					"weapon_defindex" => $defindex,
					"weapon_paint_id" => $paint,
					"weapon_wear" => $wear,
					"weapon_seed" => $seed,
					"weapon_stattrak" => $stattrak,
					"weapon_stattrak_count" => $stattrakCount,
					"weapon_nametag" => $nameTag,
					"weapon_sticker_0" => $stickerValues[0],
					"weapon_sticker_1" => $stickerValues[1],
					"weapon_sticker_2" => $stickerValues[2],
					"weapon_sticker_3" => $stickerValues[3],
					"weapon_sticker_4" => $stickerValues[4],
					"weapon_keychain" => $keychainValue,
					"team" => $targetTeam,
				];

				if ($existing) {
					$db->query("UPDATE `wp_player_skins`
						SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = :weapon_stattrak, `weapon_stattrak_count` = :weapon_stattrak_count, `weapon_nametag` = :weapon_nametag, `weapon_sticker_0` = :weapon_sticker_0, `weapon_sticker_1` = :weapon_sticker_1, `weapon_sticker_2` = :weapon_sticker_2, `weapon_sticker_3` = :weapon_sticker_3, `weapon_sticker_4` = :weapon_sticker_4, `weapon_keychain` = :weapon_keychain
						WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", $bindings);
				} else {
					$db->query("INSERT INTO `wp_player_skins`
						(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain`, `weapon_team`)
						VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, :weapon_stattrak, :weapon_stattrak_count, :weapon_nametag, :weapon_sticker_0, :weapon_sticker_1, :weapon_sticker_2, :weapon_sticker_3, :weapon_sticker_4, :weapon_keychain, :team)", $bindings);
				}

				$bindings['weapon_defindex'] = $defindex;
				saveSkinRowSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $bindings);
			}
			$db->commit();
		} catch (Throwable $exception) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $exception;
		}

		go("{$fallbackUrl}&imported=1");
	}

	// Read-only endpoint the edit page polls: the plugin keeps incrementing the
	// StatTrak counters in game, and nothing else would ever refresh them short
	// of reloading the whole page.
	if ($action === 'stattrak_counts') {
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');

		$countsPreset = findPreset($db, $presetTable, cleanSteamId($_GET['id'] ?? ''));
		if (!$countsPreset || !canEditPreset($countsPreset)) {
			echo json_encode(['ok' => false, 'counts' => []]);
			exit;
		}

		$countRows = $db->select("SELECT `weapon_defindex`, `weapon_stattrak`, `weapon_stattrak_count`
			FROM `wp_player_skins`
			WHERE `steamid` = :steamid AND `weapon_team` = :team", [
			"steamid" => $countsPreset['steamid'],
			"team" => readTeam(selectedTeam()),
		]);

		$counts = [];
		foreach ($countRows as $countRow) {
			if ((int)($countRow['weapon_stattrak'] ?? 0) !== 1) {
				continue;
			}
			$counts[(string)(int)$countRow['weapon_defindex']] = (int)($countRow['weapon_stattrak_count'] ?? 0);
		}

		echo json_encode(['ok' => true, 'counts' => $counts]);
		exit;
	}
