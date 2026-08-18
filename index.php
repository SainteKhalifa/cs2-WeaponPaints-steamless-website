<?php

require_once __DIR__ . '/class/bootstrap.php';
require __DIR__ . '/actions/bootstrap.php';
require __DIR__ . '/class/page_context.php';

$clientConfig = [
	'csrfToken' => csrfToken(),
	'stickerDataUrl' => dataFileUrl(stickerDataFile()),
	'stickerAliasDataUrl' => stickerAliasDataFile() !== '' ? dataFileUrl(stickerAliasDataFile()) : '',
	'keychainDataUrl' => dataFileUrl(keychainDataFile()),
	'keychainAliasDataUrl' => keychainAliasDataFile() !== '' ? dataFileUrl(keychainAliasDataFile()) : '',
	'paintKitDataUrl' => dataFileUrl(paintKitDataFile()),
	'paintKitAliasDataUrl' => paintKitAliasDataFile() !== '' ? dataFileUrl(paintKitAliasDataFile()) : '',
	'paintKitFinishBadges' => paintKitFinishBadges(),
	'inspectViewerUrl' => InspectLink::VIEWER_URL,
	'stattrakCountsUrl' => $action === 'edit' && isset($currentPreset['steamid'])
		? 'index.php?action=stattrak_counts&id=' . rawurlencode((string)$currentPreset['steamid']) . '&team=' . (int)($team ?? 2)
		: '',
	'requestedLoadoutPasswordId' => (string)($_GET['loadout_password_error'] ?? $_GET['loadout_password_required'] ?? ''),
	'requestedLoadoutPasswordTeam' => (string)($_GET['loadout_password_team'] ?? '1'),
	'hasLoadoutPasswordError' => isset($_GET['loadout_password_error']),
	'showAdminError' => $adminError !== '' && $accessGranted,
	'text' => [
		'stickerSlotSettings' => t('sticker_slot_settings'),
		'stickerSaveFailed' => t('sticker_save_failed'),
		'dataLoadFailed' => t('data_load_failed'),
		'fusionSourceCount' => t('fusion_source_count'),
		'fusionNativeFinish' => t('fusion_native_finish'),
		'chooseFusionFinishFor' => t('choose_fusion_finish_for'),
		'noSticker' => t('no_sticker'),
		'stickerSlot' => t('sticker_slot'),
		'keychainSaveFailed' => t('keychain_save_failed'),
		'keychain' => t('keychain'),
		'noKeychain' => t('no_keychain'),
		'validationRequired' => t('validation_required'),
		'validationNumberRange' => t('validation_number_range'),
		'validationDecimalRange' => t('validation_decimal_range'),
		'validationIntegerRange' => t('validation_integer_range'),
		'cancel' => t('cancel'),
	],
];

require __DIR__ . '/views/layout/header.php';
require __DIR__ . '/views/page.php';
require __DIR__ . '/views/layout/footer.php';

