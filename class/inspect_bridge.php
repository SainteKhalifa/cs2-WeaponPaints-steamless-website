<?php

/**
 * Glue between the loadout tables and the CS2 inspect link encoder.
 *
 * Kept apart from weapon_data.php so that upstream changes to the shared
 * helpers and this fork's bridge rarely touch the same file.
 */

require_once __DIR__ . '/inspect.php';

/**
 * Whether players may set the StatTrak kill count themselves.
 *
 * Open by default. The counter is game state the plugin maintains, so a server
 * owner who does not trust everyone with access can lock it from the config.
 */
function stattrakCountEditable()
{
	return !(defined('LOCK_STATTRAK_COUNT') && LOCK_STATTRAK_COUNT === true);
}

/**
 * Settles the StatTrak kill count to store for a weapon.
 *
 * The count is a record earned in game, so nothing here may wipe it by accident:
 * picking another skin, or switching StatTrak off and back on, leaves it alone.
 * It moves only when the plugin increments it, when a player edits it where that
 * is allowed, or when the reset button is used.
 *
 * @param array|null $existingRow Current wp_player_skins row, if any.
 * @param int|null   $submitted   Count sent by the form, null when absent.
 */
function resolveStatTrakCount($existingRow, $submitted = null)
{
	if (array_key_exists('stattrak_reset', $_POST)) {
		return 0;
	}
	if ($submitted !== null && stattrakCountEditable()) {
		return max(0, min(999999, (int)$submitted));
	}

	return max(0, (int)($existingRow['weapon_stattrak_count'] ?? 0));
}

/**
 * Builds the normalised item InspectLink expects from a database row.
 *
 * The charm field is called `template` here and `seed` in the inspect protobuf;
 * it is the same value, translated in both directions.
 */
function inspectItemFromRow($row, $defindex, $keychainValue = null)
{
	$slotCount = min(5, stickerSlotCount($defindex));
	$stickerValues = stickerValuesFromRow($row);
	$stickers = [];
	for ($i = 0; $i < $slotCount; $i++) {
		$parts = stickerValueParts($stickerValues[$i]);
		if ($parts['id'] > 0) {
			$stickers[$i] = $parts;
		}
	}

	$keychain = null;
	if ($keychainValue !== null && $keychainValue !== '') {
		$parts = keychainValueParts($keychainValue);
		if ($parts['id'] > 0) {
			$keychain = [
				'id' => $parts['id'],
				'x' => $parts['x'],
				'y' => $parts['y'],
				'z' => $parts['z'],
				'seed' => $parts['template'],
			];
		}
	}

	$row['weapon_defindex'] = (int)$defindex;
	return InspectLink::itemFromParts($row, $stickers, $keychain);
}

/**
 * Encodes one loadout piece into an inspect link, or "" when there is nothing to show.
 */
function inspectHexFromValues($defindex, $paint, $wear, $seed, $stattrak, $stattrakCount, $nameTag, $stickerValues = null, $keychainValue = null)
{
	$defindex = (int)$defindex;
	if ($defindex <= 0 || (int)$paint <= 0) {
		return '';
	}

	$row = [
		'weapon_defindex' => $defindex,
		'weapon_paint_id' => (int)$paint,
		'weapon_wear' => (float)$wear,
		'weapon_seed' => (int)$seed,
		'weapon_stattrak' => (int)$stattrak,
		'weapon_stattrak_count' => (int)$stattrakCount,
		'weapon_nametag' => $nameTag,
	];
	foreach (($stickerValues ?? defaultStickerValues()) as $slot => $value) {
		$row["weapon_sticker_{$slot}"] = $value;
	}

	return InspectLink::encode(inspectItemFromRow($row, $defindex, $keychainValue));
}

/**
 * Reference data an imported link is checked against.
 *
 * A fused loadout carries a paint kit that is not in the weapon's own skin list,
 * so those ids have to be accepted as well, matching what the skin action allows.
 */
function inspectReference($defindex, $skins, $stickers, $keychains)
{
	$paints = $skins[(int)$defindex] ?? [];
	if (skinFusionEnabled()) {
		$paints += paintKitsFromJson();
	}

	return [
		'defindex' => (int)$defindex,
		'paints' => $paints,
		'stickers' => $stickers,
		'keychains' => $keychains,
		'slots' => stickerSlotCount($defindex),
	];
}

/**
 * The "3D" button on a card. Identical for weapons, knives and gloves: it only
 * carries what the shared window needs to open.
 */
function inspectButton($defindex, $hex, $label)
{
	$disabled = $hex === '' ? ' disabled' : '';
	return '<button type="button" class="btn btn-sm btn-outline-light inspect-button" data-inspect-open'
		. ' data-inspect-defindex="' . (int)$defindex . '"'
		. ' data-inspect-hex="' . h($hex) . '"'
		. ' data-inspect-label="' . h($label) . '"'
		. ' title="' . h($hex === '' ? t('inspect_preview_unavailable') : t('inspect_title')) . '"'
		. $disabled . '>' . h(t('inspect_3d')) . '</button>';
}
