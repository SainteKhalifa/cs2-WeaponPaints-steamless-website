<?php
require_once 'class/config.php';
require_once 'class/database.php';
require_once 'class/utils.php';
require_once 'class/inspect.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
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
	setcookie($languageCookieName, $currentLanguage, time() + 60 * 60 * 24 * 365, $cookiePath);
}
UtilsClass::setLanguage($currentLanguage);
$teams = $currentLanguage === 'en' ? [1 => 'Global', 2 => 'T', 3 => 'CT'] : [1 => '全局', 2 => 'T 阵营', 3 => 'CT 阵营'];

$uiText = [
	'zh-CN' => [
		'app_title' => 'CS2 社区服皮肤管理器',
		'home_subtitle' => '使用 Steam64 ID 管理饰品配置',
		'language' => '语言',
		'select_preset' => '选择配置',
		'new_preset' => '新建配置',
		'back_home' => '返回主页',
		'back_list' => '返回配置列表',
		'nickname' => '备注用户名',
		'nickname_placeholder' => '例如：小明',
		'create' => '新建',
		'edit' => '编辑',
		'select' => '选择',
		'choose_type' => '类别',
		'choose_skin' => '皮肤',
		'choose_type_title' => '选择类别',
		'choose_skin_title' => '选择皮肤',
		'delete' => '删除',
		'save' => '保存',
		'cancel' => '取消',
		'reset' => '重置',
		'updated_notice' => '这个 Steam64 ID 已经存在，已为你更新它的备注用户名。',
		'empty_presets' => '还没有配置，先新建一个 Steam64 ID。',
		'delete_confirm' => '确定要删除这个配置吗？这会同时删除该 Steam64 ID 在 WeaponPaints 中的皮肤配置。',
		'edit_preset' => '编辑配置',
		'saved_notice' => '配置信息已保存。',
		'save_failed' => '保存失败，请检查 Steam64 ID。',
		'knife_type' => '匕首',
		'knife_model' => '刀型',
		'knife_skin' => '匕首皮肤',
		'choose_knife_first' => '请先选择刀型',
		'choose_knife_hint' => '请先选择刀型和匕首皮肤',
		'wear_value' => '磨损',
		'pattern' => '模板',
		'settings' => '设置',
		'close' => '关闭',
		'glove_type' => '手套',
		'glove_skin' => '手套皮肤',
		'default_gloves' => '使用库存手套',
		'choose_glove_first' => '请先选择手套类型',
		'choose_glove_hint' => '请先选择手套类型和手套皮肤',
		't_agent' => 'T 探员',
		'ct_agent' => 'CT 探员',
		'agent' => '探员',
		'default_agent' => '使用库存探员',
		'choose_agent' => '选择探员',
		'music_kit' => '音乐盒',
		'default_music' => '使用库存音乐盒',
		'choose_music' => '选择音乐盒',
		'search_music' => '搜索音乐盒',
		'pin' => '徽章',
		'default_pin' => '使用库存徽章',
		'choose_pin' => '选择徽章',
		'search_pin' => '搜索徽章',
		'name_tag' => '名称标签',
		'stickers' => '贴纸',
		'choose_sticker' => '选择贴纸',
		'search_sticker' => '搜索贴纸',
		'no_sticker' => '无贴纸',
		'sticker_slot' => '贴纸槽位',
		'sticker_slot_settings' => '贴纸槽位 {slot} 设置',
		'sticker_settings' => '贴纸设置',
		'sticker_wear' => 'Wear 磨损',
		'sticker_x' => 'X 偏移',
		'sticker_y' => 'Y 偏移',
		'sticker_scale' => '缩放',
		'sticker_rotation' => '旋转',
		'sticker_save_failed' => '贴纸参数保存失败，请刷新后重试。',
		'apply_sticker_to_all' => "\u{4E00}\u{952E}\u{8986}\u{76D6}\u{5168}\u{90E8}\u{8D34}\u{7EB8}\u{69FD}\u{4F4D}",
		'clear_all_stickers' => "\u{4E00}\u{952E}\u{6E05}\u{9664}\u{5168}\u{90E8}\u{8D34}\u{7EB8}",
		'access_title' => '访问密码',
		'access_prompt' => '请输入访问密码后继续进入网站',
		'access_password' => '密码',
		'access_unlock' => '进入网站',
		'access_invalid' => '密码不正确，请重试',
		'admin' => '管理员',
		'admin_enabled' => '管理员已启用',
		'admin_login' => '管理员登录',
		'admin_password' => '管理员密码',
		'admin_disabled' => '管理员功能未启用',
		'admin_enter' => '确认',
		'admin_exit' => '退出',
		'admin_invalid' => '管理员密码错误',
		'enable_loadout_password' => '启用 PIN',
		'enter_loadout_password' => '输入 PIN',
		'loadout_password_prompt' => '此配置已启用 PIN，请验证后继续。',
		'loadout_password_incorrect' => 'PIN 错误，请重试。',
		'loadout_password_set_placeholder' => '输入要设置的 PIN',
		'loadout_password_change_placeholder' => '输入要修改的 PIN，留空则保持不变。',
		'loadout_password_enabled' => '已启用 PIN',
		'loadout_password_disabled' => '未启用 PIN',
		'loadout_password_label' => 'PIN',
		'loadout_password_required' => '请先输入 PIN。',
		'basic_info' => '基础信息',
		'loadout_password_protection' => 'PIN 保护',
		'loadout_password_optional_hint' => '启用后，进入和修改此配置需要验证 PIN。',
		'invalid_steamid' => '请输入正确的 Steam64 ID',
		'validation_required' => '请填写此字段',
		'validation_number_range' => '请输入 {min} 到 {max} 之间的数字',
		'validation_integer_range' => '请输入 {min} 到 {max} 之间的整数',
		'keychain' => '挂件',
		'no_keychain' => '不使用挂件',
		'choose_keychain' => '选择挂件',
		'search_keychain' => '搜索挂件',
		'keychain_seed' => '挂件种子',
		'inspect_3d_edit' => '3D 编辑',
		'inspect_3d_hint' => '在 3D 预览中调整贴纸和挂件位置，然后复制检视链接并粘贴到下方。',
		'inspect_import' => '导入检视链接',
		'inspect_import_placeholder' => '粘贴检视链接或十六进制代码',
		'inspect_import_apply' => '导入',
		'inspect_preview' => '3D 预览',
		'inspect_imported' => '检视链接导入成功，请在游戏中重新执行 !ws 或重生以生效。',
		'inspect_error_invalid' => '无法识别该检视链接，请复制完整链接后重试。',
		'inspect_error_weapon_mismatch' => '该检视链接对应的武器与当前武器不符。',
		'inspect_error_unknown_paint' => '该检视链接的皮肤不在可用列表中。',
		'inspect_error_failed' => '导入失败，请刷新后重试。',
	],
	'en' => [
		'app_title' => 'CS2 Community Server Skin Manager',
		'home_subtitle' => 'Manage your skins with Steam64 ID.',
		'language' => 'Language',
		'select_preset' => 'Select Loadout',
		'new_preset' => 'New Loadout',
		'back_home' => 'Back Home',
		'back_list' => 'Back to Loadouts',
		'nickname' => 'Nickname',
		'nickname_placeholder' => 'Example: Alex',
		'create' => 'Create',
		'edit' => 'Edit',
		'select' => 'Select',
		'choose_type' => 'Category',
		'choose_skin' => 'Skin',
		'choose_type_title' => 'Choose Category',
		'choose_skin_title' => 'Choose Skin',
		'delete' => 'Delete',
		'save' => 'Save',
		'cancel' => 'Cancel',
		'reset' => 'Reset',
		'updated_notice' => 'This Steam64 ID already exists, so its nickname has been updated.',
		'empty_presets' => 'No loadouts yet. Add a Steam64 ID first.',
		'delete_confirm' => 'Delete this loadout? This will also delete this Steam64 ID\'s WeaponPaints skin settings.',
		'edit_preset' => 'Edit Loadout',
		'saved_notice' => 'Loadout info saved.',
		'save_failed' => 'Save failed. Please check the Steam64 ID.',
		'knife_type' => 'Knife Type',
		'knife_model' => 'Knife Model',
		'knife_skin' => 'Knife Skin',
		'choose_knife_first' => 'Choose a knife model first',
		'choose_knife_hint' => 'Choose a knife model and knife skin first.',
		'wear_value' => 'Wear Rating',
		'pattern' => 'Pattern Template',
		'settings' => 'Settings',
		'close' => 'Close',
		'glove_type' => 'Glove Type',
		'glove_skin' => 'Glove Skin',
		'default_gloves' => 'Use inventory gloves',
		'choose_glove_first' => 'Choose a glove type first',
		'choose_glove_hint' => 'Choose a glove type and glove skin first.',
		't_agent' => 'T Agent',
		'ct_agent' => 'CT Agent',
		'agent' => 'Agent',
		'default_agent' => 'Use inventory agent',
		'choose_agent' => 'Choose Agent',
		'music_kit' => 'Music Kit',
		'default_music' => 'Use inventory music kit',
		'choose_music' => 'Choose Music Kit',
		'search_music' => 'Search music kits',
		'pin' => 'Pin',
		'default_pin' => 'Use inventory pin',
		'choose_pin' => 'Choose Pin',
		'search_pin' => 'Search pins',
		'name_tag' => 'Name Tag',
		'stickers' => 'Stickers',
		'choose_sticker' => 'Choose Sticker',
		'search_sticker' => 'Search stickers',
		'no_sticker' => 'No sticker',
		'sticker_slot' => 'Sticker Slot',
		'sticker_slot_settings' => 'Sticker Slot {slot} Settings',
		'sticker_settings' => 'Sticker Settings',
		'sticker_wear' => 'Wear',
		'sticker_x' => 'X Offset',
		'sticker_y' => 'Y Offset',
		'sticker_scale' => 'Scale',
		'sticker_rotation' => 'Rotation',
		'sticker_save_failed' => 'Failed to save sticker settings. Please refresh and try again.',
		'apply_sticker_to_all' => 'Apply sticker to all slots',
		'clear_all_stickers' => 'Clear all stickers',
		'access_title' => 'Access Password',
		'access_prompt' => 'Please enter the access password to continue to the website.',
		'access_password' => 'Password',
		'access_unlock' => 'Enter Site',
		'access_invalid' => 'Incorrect password. Please try again.',
		'admin' => 'Administrator',
		'admin_enabled' => 'Administrator enabled',
		'admin_login' => 'Administrator Login',
		'admin_password' => 'Administrator Password',
		'admin_disabled' => 'Administrator mode is not enabled.',
		'admin_enter' => 'Confirm',
		'admin_exit' => 'Exit',
		'admin_invalid' => 'Incorrect administrator password.',
		'enable_loadout_password' => 'Enable PIN',
		'enter_loadout_password' => 'Enter PIN',
		'loadout_password_prompt' => 'This loadout is protected by a PIN. Verify it to continue.',
		'loadout_password_incorrect' => 'Incorrect PIN. Please try again.',
		'loadout_password_set_placeholder' => 'Enter a PIN to set',
		'loadout_password_change_placeholder' => 'Enter a new PIN. Leave blank to keep current.',
		'loadout_password_enabled' => 'PIN enabled',
		'loadout_password_disabled' => 'PIN disabled',
		'loadout_password_label' => 'PIN',
		'loadout_password_required' => 'Please enter a PIN first.',
		'basic_info' => 'Basic Information',
		'loadout_password_protection' => 'PIN Protection',
		'loadout_password_optional_hint' => 'When enabled, opening and modifying this loadout requires PIN verification.',
		'invalid_steamid' => 'Please enter a valid Steam64 ID.',
		'validation_required' => 'Please fill out this field.',
		'validation_number_range' => 'Please enter a number from {min} to {max}.',
		'validation_integer_range' => 'Please enter an integer from {min} to {max}.',
		'keychain' => 'Charm',
		'no_keychain' => 'No charm',
		'choose_keychain' => 'Choose a charm',
		'search_keychain' => 'Search charms',
		'keychain_seed' => 'Charm seed',
		'inspect_3d_edit' => 'Edit in 3D',
		'inspect_3d_hint' => 'Place your stickers and charm in the 3D preview, then copy the inspect link and paste it below.',
		'inspect_import' => 'Import inspect link',
		'inspect_import_placeholder' => 'Paste an inspect link or hex payload',
		'inspect_import_apply' => 'Import',
		'inspect_preview' => '3D preview',
		'inspect_imported' => 'Inspect link imported. Run !ws again or respawn in game to apply it.',
		'inspect_error_invalid' => 'That inspect link could not be read. Copy the full link and try again.',
		'inspect_error_weapon_mismatch' => 'That inspect link is for a different weapon.',
		'inspect_error_unknown_paint' => 'That inspect link uses a skin that is not available here.',
		'inspect_error_failed' => 'Import failed. Please refresh and try again.',
	],
];

function t($key)
{
	global $uiText, $currentLanguage;
	return $uiText[$currentLanguage][$key] ?? $uiText['zh-CN'][$key] ?? $key;
}

function h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function go($url)
{
	header("Location: {$url}");
	exit;
}

function languageUrl($language)
{
	$query = $_GET;
	$query['lang'] = $language;
	return 'index.php?' . http_build_query($query);
}

function ensurePresetTable($db, $presetTable)
{
	$db->query("CREATE TABLE IF NOT EXISTS `{$presetTable}` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`steamid` VARCHAR(32) NOT NULL,
		`nickname` VARCHAR(100) NULL,
		`loadout_password_hash` VARCHAR(255) NULL,
		`created_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY `uniq_steamid` (`steamid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	if (!columnExists($db, $presetTable, 'loadout_password_hash')) {
		$db->query("ALTER TABLE `{$presetTable}` ADD `loadout_password_hash` VARCHAR(255) NULL AFTER `nickname`");
	}
	$legacyLoadoutPasswordHashColumn = 'edit_' . 'pi' . 'n_hash';
	if (columnExists($db, $presetTable, $legacyLoadoutPasswordHashColumn)) {
		$db->query("UPDATE `{$presetTable}` SET `loadout_password_hash` = `{$legacyLoadoutPasswordHashColumn}` WHERE `loadout_password_hash` IS NULL AND `{$legacyLoadoutPasswordHashColumn}` IS NOT NULL");
	}
}

function ensureSkinSettingsTable($db, $skinSettingsTable)
{
	$db->query("CREATE TABLE IF NOT EXISTS `{$skinSettingsTable}` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`steamid` VARCHAR(32) NOT NULL,
		`weapon_team` INT NOT NULL,
		`weapon_defindex` INT NOT NULL,
		`weapon_paint_id` INT NOT NULL,
		`weapon_wear` FLOAT NOT NULL DEFAULT 0,
		`weapon_seed` INT NOT NULL DEFAULT 0,
		`weapon_stattrak` TINYINT(1) NOT NULL DEFAULT 0,
		`weapon_stattrak_count` INT NOT NULL DEFAULT 0,
		`weapon_nametag` VARCHAR(64) NULL,
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
}

function tableExists($db, $table)
{
	return count($db->select("SHOW TABLES LIKE :table_name", ["table_name" => $table])) > 0;
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

function selectedTeam()
{
	$team = (int)($_GET['team'] ?? $_POST['team'] ?? 1);
	return in_array($team, [1, 2, 3], true) ? $team : 1;
}

function readTeam($team)
{
	return $team === 1 ? 2 : $team;
}

function writeTeams($team)
{
	return $team === 1 ? [2, 3] : [$team];
}

function cleanSteamId($steamid)
{
	return trim((string)$steamid);
}

function knifeDefindexes($knifes)
{
	return array_values(array_filter(array_map('intval', array_keys($knifes)), static fn($key) => $key > 0));
}

function agentsFromJson()
{
	return UtilsClass::agentsFromJson();
}

function musicFromJson()
{
	$music = [
		0 => [
			'id' => 0,
			'name' => t('default_music'),
			'image' => '',
		],
	];
	foreach (UtilsClass::musicFromJson() as $musicKit) {
		$id = (int)($musicKit['id'] ?? 0);
		$music[$id] = [
			'id' => $id,
			'name' => $musicKit['name'] ?? '',
			'image' => $musicKit['image'] ?? '',
		];
	}
	ksort($music);
	return $music;
}

function pinsFromJson()
{
	$pins = [
		0 => [
			'id' => 0,
			'name' => t('default_pin'),
			'image' => '',
		],
	];
	foreach (UtilsClass::pinsFromJson() as $pin) {
		$id = (int)($pin['id'] ?? 0);
		$pins[$id] = [
			'id' => $id,
			'name' => $pin['name'] ?? '',
			'image' => $pin['image'] ?? '',
		];
	}
	ksort($pins);
	return $pins;
}

function itemAliasNamesFromJson($languageFile, $nameKey = 'name')
{
	$aliases = [];
	foreach (UtilsClass::dataFromJson($languageFile, $languageFile) as $item) {
		$id = (int)($item['id'] ?? 0);
		if ($id > 0) {
			$aliases[$id] = (string)($item[$nameKey] ?? '');
		}
	}
	return $aliases;
}


function stickersFromJson()
{
	$stickers = [
		0 => [
			'id' => 0,
			'name' => t('no_sticker'),
			'image' => '',
		],
	];
	foreach (UtilsClass::stickersFromJson() as $sticker) {
		$id = (int)($sticker['id'] ?? 0);
		$stickers[$id] = [
			'id' => $id,
			'name' => $sticker['name'] ?? '',
			'image' => $sticker['image'] ?? '',
		];
	}
	ksort($stickers);
	return $stickers;
}

function defaultStickerValue()
{
	return '0;0;0;0;0;0;0';
}

function stickerSlotCount($defindex)
{
	return in_array((int)$defindex, [11, 23, 60, 61, 64], true) ? 5 : 4;
}

function stickerNumber($value, $min, $max, $default, $scaleMustBePositive = false)
{
	if ($value === null || $value === '' || !is_numeric($value)) {
		return $default;
	}
	$value = (float)$value;
	if ($scaleMustBePositive && $value <= 0) {
		return $default;
	}
	return max((float)$min, min((float)$max, $value));
}

function stickerValueParts($value)
{
	$parts = array_pad(explode(';', (string)$value), 7, '');
	$id = max(0, (int)($parts[0] ?? 0));
	$schema = max(0, (int)($parts[1] ?? 0));
	if ($id > 0 && $schema === 0) {
		$schema = $id;
	}
	return [
		'id' => $id,
		'schema' => $schema,
		'x' => stickerNumber($parts[2] ?? null, -1, 1, 0),
		'y' => stickerNumber($parts[3] ?? null, -1, 1, 0),
		'wear' => stickerNumber($parts[4] ?? null, 0, 1, 0),
		'scale' => stickerNumber($parts[5] ?? null, 0.2, 5, 1, true),
		'rotation' => stickerNumber($parts[6] ?? null, 0, 360, 0),
	];
}

function stickerIdFromValue($value)
{
	$parts = stickerValueParts($value);
	return $parts['id'];
}

function stickerFloatValue($value)
{
	return number_format((float)$value, 2, '.', '');
}

function buildStickerValueFromParts($id, $schema, $params)
{
	$id = max(0, (int)$id);
	$schema = max(0, (int)$schema);
	if ($id === 0) {
		return defaultStickerValue();
	}
	if ($schema === 0) {
		$schema = $id;
	}
	$x = stickerFloatValue(stickerNumber($params['x'] ?? 0, -1, 1, 0));
	$y = stickerFloatValue(stickerNumber($params['y'] ?? 0, -1, 1, 0));
	$wear = stickerFloatValue(stickerNumber($params['wear'] ?? 0, 0, 1, 0));
	$scale = stickerFloatValue(stickerNumber($params['scale'] ?? 1, 0.2, 5, 1, true));
	$rotation = (string)(int)round(stickerNumber($params['rotation'] ?? 0, 0, 360, 0));
	return "{$id};{$schema};{$x};{$y};{$wear};{$scale};{$rotation}";
}

function buildStickerValue($stickerId)
{
	$stickerId = max(0, (int)$stickerId);
	if ($stickerId === 0) {
		return defaultStickerValue();
	}
	return buildStickerValueFromParts($stickerId, $stickerId, [
		'x' => 0,
		'y' => 0,
		'wear' => 0,
		'scale' => 1,
		'rotation' => 0,
	]);
}

function readStickerAdvancedParamsFromPost()
{
	return [
		'wear' => stickerNumber($_POST['sticker_wear'] ?? null, 0, 1, 0),
		'x' => stickerNumber($_POST['sticker_x'] ?? null, -1, 1, 0),
		'y' => stickerNumber($_POST['sticker_y'] ?? null, -1, 1, 0),
		'scale' => stickerNumber($_POST['sticker_scale'] ?? null, 0.2, 5, 1, true),
		'rotation' => stickerNumber($_POST['sticker_rotation'] ?? null, 0, 360, 0),
	];
}

function wantsJsonResponse()
{
	$requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
	$accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
	return ($_POST['ajax'] ?? '') === '1' || $requestedWith === 'fetch' || strpos($accept, 'application/json') !== false;
}

function stickerSlotResponse($ok, $payload, $fallbackUrl)
{
	if (wantsJsonResponse()) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['ok' => $ok] + $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	go($fallbackUrl);
}
function defaultStickerValues()
{
	return array_fill(0, 5, defaultStickerValue());
}

function stickerValuesFromRow($row)
{
	$values = defaultStickerValues();
	for ($i = 0; $i < 5; $i++) {
		$key = "weapon_sticker_{$i}";
		if (isset($row[$key]) && $row[$key] !== '') {
			$values[$i] = $row[$key];
		}
	}
	return $values;
}

function readStickerValuesFromPost($slotCount, $stickers)
{
	if (!array_key_exists('sticker_present', $_POST)) {
		return null;
	}
	$values = defaultStickerValues();
	for ($i = 0; $i < min(5, (int)$slotCount); $i++) {
		$stickerId = (int)($_POST["sticker_{$i}"] ?? 0);
		if (!array_key_exists($stickerId, $stickers)) {
			$stickerId = 0;
		}
		$postedValue = (string)($_POST["sticker_value_{$i}"] ?? '');
		$postedParts = stickerValueParts($postedValue);
		if ($stickerId > 0 && $postedParts['id'] === $stickerId) {
			$values[$i] = buildStickerValueFromParts($postedParts['id'], $postedParts['schema'], $postedParts);
		} else {
			$values[$i] = buildStickerValue($stickerId);
		}
	}
	return $values;
}
function readKeychainValueFromPost($keychains)
{
	if (!array_key_exists('keychain_present', $_POST)) {
		return null;
	}
	$id = (int)($_POST['keychain_id'] ?? 0);
	if (!array_key_exists($id, $keychains)) {
		$id = 0;
	}
	if ($id === 0) {
		return defaultKeychainValue();
	}
	// Les offsets viennent d'un import 3D : on les conserve tant que le charm
	// reste le même, seul le seed est modifiable depuis le formulaire.
	$posted = keychainValueParts((string)($_POST['keychain_value'] ?? ''));
	$keepOffsets = $posted['id'] === $id;
	return buildKeychainValueFromParts($id, [
		'x' => $keepOffsets ? $posted['x'] : 0,
		'y' => $keepOffsets ? $posted['y'] : 0,
		'z' => $keepOffsets ? $posted['z'] : 0,
		'seed' => array_key_exists('keychain_seed', $_POST) ? (int)$_POST['keychain_seed'] : ($keepOffsets ? $posted['seed'] : 0),
	]);
}

function keychainsFromJson()
{
	$keychains = [
		0 => [
			'id' => 0,
			'name' => t('no_keychain'),
			'image' => '',
		],
	];
	foreach (UtilsClass::keychainsFromJson() as $keychain) {
		$id = (int)($keychain['id'] ?? 0);
		$keychains[$id] = [
			'id' => $id,
			'name' => $keychain['name'] ?? '',
			'image' => $keychain['image'] ?? '',
		];
	}
	ksort($keychains);
	return $keychains;
}

function defaultKeychainValue()
{
	return '0;0;0;0;0';
}

function keychainValueParts($value)
{
	$parts = array_pad(explode(';', (string)$value), 5, '');
	return [
		'id' => max(0, (int)($parts[0] ?? 0)),
		'x' => stickerNumber($parts[1] ?? null, -100, 100, 0),
		'y' => stickerNumber($parts[2] ?? null, -100, 100, 0),
		'z' => stickerNumber($parts[3] ?? null, -100, 100, 0),
		'seed' => max(0, min(100000, (int)($parts[4] ?? 0))),
	];
}

function buildKeychainValueFromParts($id, $params)
{
	$id = max(0, (int)$id);
	if ($id === 0) {
		return defaultKeychainValue();
	}
	$x = stickerFloatValue(stickerNumber($params['x'] ?? 0, -100, 100, 0));
	$y = stickerFloatValue(stickerNumber($params['y'] ?? 0, -100, 100, 0));
	$z = stickerFloatValue(stickerNumber($params['z'] ?? 0, -100, 100, 0));
	$seed = max(0, min(100000, (int)($params['seed'] ?? 0)));
	return "{$id};{$x};{$y};{$z};{$seed}";
}

/**
 * La colonne `weapon_keychain` n'existe que sur les schémas WeaponPaints récents.
 */
function keychainColumnAvailable($db)
{
	static $available = null;
	if ($available === null) {
		$available = columnExists($db, 'wp_player_skins', 'weapon_keychain');
	}
	return $available;
}

function keychainDataFile()
{
	$currentLanguage = UtilsClass::currentLanguage();
	$language = in_array($currentLanguage, ['zh-CN', 'en'], true) ? "keychains_{$currentLanguage}" : (defined('KEYCHAIN_LANGUAGE') ? KEYCHAIN_LANGUAGE : 'keychains_en');
	if (!is_file(__DIR__ . "/data/{$language}.json")) {
		$language = 'keychains_en';
	}
	return "data/{$language}.json";
}

/**
 * Construit l'item normalisé attendu par InspectLink depuis une ligne de base.
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
			$keychain = $parts;
		}
	}

	$row['weapon_defindex'] = (int)$defindex;
	return InspectLink::itemFromParts($row, $stickers, $keychain);
}

/**
 * Données de référence contre lesquelles un lien importé est recoupé.
 */
function inspectReference($defindex, $skins, $stickers, $keychains)
{
	return [
		'defindex' => (int)$defindex,
		'paints' => $skins[(int)$defindex] ?? [],
		'stickers' => $stickers,
		'keychains' => $keychains,
		'slots' => stickerSlotCount($defindex),
	];
}

function stickerDataFile()
{
	$currentLanguage = UtilsClass::currentLanguage();
	$language = in_array($currentLanguage, ['zh-CN', 'en'], true) ? "stickers_{$currentLanguage}" : (defined('STICKER_LANGUAGE') ? STICKER_LANGUAGE : 'stickers_en');
	if (!is_file(__DIR__ . "/data/{$language}.json")) {
		$language = 'stickers_en';
	}
	return "data/{$language}.json";
}

function dataFileUrl($relativeFile)
{
	$path = __DIR__ . '/' . $relativeFile;
	$version = is_file($path) ? filemtime($path) : time();
	return $relativeFile . '?v=' . $version;
}

function stickerAliasDataFile()
{
	$current = stickerDataFile();
	$english = 'data/stickers_en.json';
	if ($current === $english || !is_file(__DIR__ . '/' . $english)) {
		return '';
	}
	return $english;
}

function glovesFromJson()
{
	$gloves = [];
	foreach (UtilsClass::glovesFromJson() as $glove) {
		$defindex = (int)($glove['weapon_defindex'] ?? 0);
		$paint = (int)($glove['paint'] ?? 0);
		$gloves[$defindex][$paint] = [
			'weapon_defindex' => $defindex,
			'paint' => $paint,
			'paint_name' => $glove['paint_name'] ?? '',
			'image_url' => $glove['image'] ?? '',
		];
	}
	ksort($gloves);
	return $gloves;
}

function gloveDefindexes($gloves)
{
	return array_values(array_filter(array_map('intval', array_keys($gloves)), static fn($key) => $key > 0));
}

function gloveTypeOptions($gloves)
{
	$options = [];
	foreach ($gloves as $defindex => $paints) {
		$first = reset($paints);
		if (!$first) {
			continue;
		}
		$name = $first['paint_name'];
		if ((int)$defindex === 0) {
			$name = UtilsClass::currentLanguage() === 'en' ? 'Use inventory gloves' : '使用库存手套';
		} elseif (str_contains($name, '|')) {
			$name = trim(explode('|', $name, 2)[0]);
		} else {
			$name = preg_replace('/^(使用库存|Use inventory)\s+/u', '', $name);
		}
		$options[(int)$defindex] = [
			'paint_name' => $name,
			'image_url' => $first['image_url'],
		];
	}
	return $options;
}

function glovePlaceholderImage($defindex)
{
	if ((int)$defindex === 0) {
		return 'img/skins/gloves.png';
	}

	$placeholders = [
		4725 => 'studded_brokenfang_gloves.png',
		5027 => 'studded_bloodhound_gloves.png',
		5030 => 'sporty_gloves.png',
		5031 => 'slick_gloves.png',
		5032 => 'leather_handwraps.png',
		5033 => 'motorcycle_gloves.png',
		5034 => 'specialist_gloves.png',
		5035 => 'studded_hydra_gloves.png',
	];
	$file = $placeholders[(int)$defindex] ?? '';
	return $file !== '' ? "img/skins/{$file}" : '';
}

function weaponPlaceholderImage($weaponName)
{
	$weaponName = basename((string)$weaponName);
	if ($weaponName === '') {
		return '';
	}
	if ($weaponName === 'weapon_knife') {
		return 'img/skins/knife.png';
	}
	$path = "img/weapon/{$weaponName}.png";
	return is_file(__DIR__ . "/{$path}") ? $path : '';
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

function saveSkinSettingCache($db, $skinSettingsTable, $steamid, $team, $defindex, $paint, $wear, $seed, $stattrak, $stattrakCount, $nameTag)
{
	$stattrakCount = max(0, min(999999, (int)$stattrakCount));
	$db->query("INSERT INTO `{$skinSettingsTable}`
		(`steamid`, `weapon_team`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`)
		VALUES (:steamid, :team, :defindex, :paint, :wear, :seed, :stattrak, :stattrak_count, :nametag)
		ON DUPLICATE KEY UPDATE
			`weapon_wear` = :wear_update,
			`weapon_seed` = :seed_update,
			`weapon_stattrak` = :stattrak_update,
			`weapon_stattrak_count` = :stattrak_count_update,
			`weapon_nametag` = :nametag_update", [
		"steamid" => $steamid,
		"team" => $team,
		"defindex" => $defindex,
		"paint" => $paint,
		"wear" => $wear,
		"seed" => $seed,
		"stattrak" => $stattrak,
		"stattrak_count" => $stattrakCount,
		"nametag" => $nameTag,
		"wear_update" => $wear,
		"seed_update" => $seed,
		"stattrak_update" => $stattrak,
		"stattrak_count_update" => $stattrakCount,
		"nametag_update" => $nameTag,
	]);
}

function loadSkinSettingCache($db, $skinSettingsTable, $steamid, $team, $defindex, $paint)
{
	$rows = $db->select("SELECT `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`
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

$message = '';
$error = '';
$accessError = false;
$adminError = false;
$action = $_GET['action'] ?? 'home';
$accessPassword = defined('SITE_ACCESS_PASSWORD') ? (string)SITE_ACCESS_PASSWORD : '';
$accessRequired = $accessPassword !== '';
$accessSessionKey = $accessRequired ? hash('sha256', $accessPassword) : '';
$accessGranted = !$accessRequired || (($_SESSION['cs2_site_access_granted'] ?? '') === $accessSessionKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_access') {
	$submittedPassword = (string)($_POST['access_password'] ?? '');
	if ($accessRequired && hash_equals($accessPassword, $submittedPassword)) {
		$_SESSION['cs2_site_access_granted'] = $accessSessionKey;
		session_regenerate_id(true);
		go('index.php');
	}
	$accessError = true;
	$action = 'access';
}

if (!$accessGranted) {
	$action = 'access';
}

if ($accessGranted) {
	$db = new DataBase();
	ensurePresetTable($db, $presetTable);
	ensureSkinSettingsTable($db, $skinSettingsTable);
}

if ($accessGranted && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_login') {
	$returnTo = safeReturnUrl($_POST['return_to'] ?? 'index.php');
	$submittedPassword = (string)($_POST['admin_password'] ?? '');
	if (adminPassword() !== '' && hash_equals(adminPassword(), $submittedPassword)) {
		$_SESSION['is_admin'] = true;
		$_SESSION['cs2_admin_key'] = hash('sha256', adminPassword());
		session_regenerate_id(true);
		go($returnTo);
	}
	$_SESSION['cs2_admin_error'] = true;
	go($returnTo);
}

if ($accessGranted && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_logout') {
	unset($_SESSION['is_admin'], $_SESSION['cs2_admin_key']);
	session_regenerate_id(true);
	go(safeReturnUrl($_POST['return_to'] ?? 'index.php'));
}

if ($accessGranted && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_loadout_password') {
	$id = cleanSteamId($_POST['id'] ?? '');
	$team = selectedTeam();
	$preset = findPreset($db, $presetTable, $id);
	$submittedLoadoutPassword = (string)($_POST['loadout_password'] ?? '');
	if ($preset && (isAdmin() || !loadoutHasPassword($preset) || password_verify($submittedLoadoutPassword, (string)$preset['loadout_password_hash']))) {
		if (loadoutHasPassword($preset) && !isAdmin()) {
			markLoadoutPasswordVerified($preset);
		}
		session_regenerate_id(true);
		go(editUrl($preset, $team));
	}
	go('index.php?action=list&loadout_password_error=' . rawurlencode($id) . '&loadout_password_team=' . $team);
}

$adminError = !empty($_SESSION['cs2_admin_error']);
unset($_SESSION['cs2_admin_error']);

if ($accessGranted && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$postAction = $_POST['action'] ?? '';

	if ($postAction === 'create_preset') {
		$steamid = cleanSteamId($_POST['steamid'] ?? '');
		$nickname = trim((string)($_POST['nickname'] ?? ''));
		$enableLoadoutPassword = isset($_POST['enable_loadout_password']);
		$newLoadoutPassword = (string)($_POST['loadout_password'] ?? '');
		if (!preg_match('/^\d{5,32}$/', $steamid)) {
			$error = t('invalid_steamid');
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
				$loadoutPasswordHash = $enableLoadoutPassword ? password_hash($newLoadoutPassword, PASSWORD_DEFAULT) : $existingPreset['loadout_password_hash'];
				$db->query("UPDATE `{$presetTable}` SET `nickname` = :nickname, `loadout_password_hash` = :loadout_password_hash WHERE `steamid` = :steamid", [
					"steamid" => $steamid,
					"nickname" => $nickname !== '' ? $nickname : null,
					"loadout_password_hash" => $loadoutPasswordHash,
				]);
				if ($enableLoadoutPassword && !isAdmin()) {
					$existingPreset['loadout_password_hash'] = $loadoutPasswordHash;
					markLoadoutPasswordVerified($existingPreset);
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
		if ($preset && canEditPreset($preset)) {
			$steamid = $preset['steamid'];
			foreach (['wp_player_skins', 'wp_player_knife', 'wp_player_agents', 'wp_player_gloves', 'wp_player_music', 'wp_player_pins'] as $table) {
				if (tableExists($db, $table)) {
					$db->query("DELETE FROM `{$table}` WHERE `steamid` = :steamid", ["steamid" => $steamid]);
				}
			}
			$db->query("DELETE FROM `{$skinSettingsTable}` WHERE `steamid` = :steamid", ["steamid" => $steamid]);
			$db->query("DELETE FROM `{$presetTable}` WHERE `steamid` = :steamid", ["steamid" => $steamid]);
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

		if (!$preset || !canEditPreset($preset) || !preg_match('/^\d{5,32}$/', $steamid)) {
			go("index.php?action=edit&id={$id}&team={$team}&error=identity");
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
		$db->query("UPDATE `{$presetTable}` SET `steamid` = :steamid, `nickname` = :nickname, `loadout_password_hash` = :loadout_password_hash WHERE `steamid` = :old_steamid", [
			"steamid" => $steamid,
			"nickname" => $nickname !== '' ? $nickname : null,
			"loadout_password_hash" => $loadoutPasswordHash,
			"old_steamid" => $oldSteamid,
		]);
		$updatedPreset = $preset;
		$updatedPreset['steamid'] = $steamid;
		$updatedPreset['loadout_password_hash'] = $loadoutPasswordHash;
		if ($enableLoadoutPassword && !isAdmin()) {
			markLoadoutPasswordVerified($updatedPreset);
		} else {
			clearLoadoutPasswordVerification($preset);
		}

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
		go("index.php?action=edit&id={$steamid}&team={$team}&saved=1");
	}

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
		if (!$preset || !canEditPreset($preset) || $defindex <= 0 || $slot < 0 || $slot >= min(5, $slotCount) || !array_key_exists($stickerId, $stickers)) {
			stickerSlotResponse(false, ['message' => t('sticker_save_failed')], $fallbackUrl);
		}

		$field = "weapon_sticker_{$slot}";
		$newValue = buildStickerValue($stickerId);
		$updated = false;
		foreach (writeTeams($team) as $targetTeam) {
			$rows = $db->select("SELECT `weapon_defindex` FROM `wp_player_skins`
				WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
				"steamid" => $preset['steamid'],
				"weapon_defindex" => $defindex,
				"team" => $targetTeam,
			]);
			if (!$rows) {
				continue;
			}
			$db->query("UPDATE `wp_player_skins` SET `{$field}` = :sticker_value
				WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
				"sticker_value" => $newValue,
				"steamid" => $preset['steamid'],
				"weapon_defindex" => $defindex,
				"team" => $targetTeam,
			]);
			$updated = true;
		}

		if (!$updated) {
			stickerSlotResponse(false, ['message' => t('sticker_save_failed')], $fallbackUrl);
		}
		stickerSlotResponse(true, [
			'value' => $newValue,
			'slot' => $slot,
			'sticker_id' => $stickerId,
			'params' => stickerValueParts($newValue),
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
		if (!$preset || !canEditPreset($preset) || $defindex <= 0 || $slot < 0 || $slot >= min(5, $slotCount)) {
			stickerSlotResponse(false, ['message' => t('sticker_save_failed')], $fallbackUrl);
		}

		$field = "weapon_sticker_{$slot}";
		$params = readStickerAdvancedParamsFromPost();
		$responseValue = null;
		$updated = false;
		foreach (writeTeams($team) as $targetTeam) {
			$rows = $db->select("SELECT `{$field}` AS sticker_value FROM `wp_player_skins`
				WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
				"steamid" => $preset['steamid'],
				"weapon_defindex" => $defindex,
				"team" => $targetTeam,
			]);
			if (!$rows) {
				continue;
			}
			$parts = stickerValueParts($rows[0]['sticker_value'] ?? '');
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
			if ($responseValue === null) {
				$responseValue = $newValue;
			}
			$updated = true;
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

		// Le lien vient du joueur : tout est recoupé avec les données du site.
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
		$hasKeychainColumn = keychainColumnAvailable($db);

		$stickerValues = defaultStickerValues();
		foreach ($item['stickers'] as $sticker) {
			$stickerValues[(int)$sticker['slot']] = buildStickerValueFromParts($sticker['id'], $sticker['id'], $sticker);
		}
		$keychainValue = $item['keychain'] !== null
			? buildKeychainValueFromParts($item['keychain']['id'], $item['keychain'])
			: defaultKeychainValue();

		$paint = (int)$item['paintindex'];
		$wear = (float)$item['paintwear'];
		$seed = (int)$item['paintseed'];
		$stattrak = $item['stattrak'] ? 1 : 0;
		$stattrakCount = $stattrak ? (int)$item['stattrak_count'] : 0;
		$nameTag = $item['customname'] !== '' ? $item['customname'] : null;

		$assignments = [
			'`weapon_paint_id` = :weapon_paint_id',
			'`weapon_wear` = :weapon_wear',
			'`weapon_seed` = :weapon_seed',
			'`weapon_stattrak` = :weapon_stattrak',
			'`weapon_stattrak_count` = :weapon_stattrak_count',
			'`weapon_nametag` = :weapon_nametag',
			'`weapon_sticker_0` = :weapon_sticker_0',
			'`weapon_sticker_1` = :weapon_sticker_1',
			'`weapon_sticker_2` = :weapon_sticker_2',
			'`weapon_sticker_3` = :weapon_sticker_3',
			'`weapon_sticker_4` = :weapon_sticker_4',
		];
		$insertColumns = ['`steamid`', '`weapon_defindex`', '`weapon_paint_id`', '`weapon_wear`', '`weapon_seed`', '`weapon_stattrak`', '`weapon_stattrak_count`', '`weapon_nametag`', '`weapon_sticker_0`', '`weapon_sticker_1`', '`weapon_sticker_2`', '`weapon_sticker_3`', '`weapon_sticker_4`', '`weapon_team`'];
		$insertValues = [':steamid', ':weapon_defindex', ':weapon_paint_id', ':weapon_wear', ':weapon_seed', ':weapon_stattrak', ':weapon_stattrak_count', ':weapon_nametag', ':weapon_sticker_0', ':weapon_sticker_1', ':weapon_sticker_2', ':weapon_sticker_3', ':weapon_sticker_4', ':team'];
		if ($hasKeychainColumn) {
			$assignments[] = '`weapon_keychain` = :weapon_keychain';
			array_splice($insertColumns, -1, 0, ['`weapon_keychain`']);
			array_splice($insertValues, -1, 0, [':weapon_keychain']);
		}

		$isKnifeSkin = in_array($defindex, knifeDefindexes($knifes), true);
		$updated = false;
		foreach (writeTeams($team) as $targetTeam) {
			if ($isKnifeSkin) {
				$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`)
					VALUES(:steamid, :knife, :team)
					ON DUPLICATE KEY UPDATE `knife` = :knife_update", [
					"steamid" => $steamid,
					"knife" => $knifes[$defindex]['weapon_name'],
					"team" => $targetTeam,
					"knife_update" => $knifes[$defindex]['weapon_name'],
				]);
			}

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
				"team" => $targetTeam,
			];
			if ($hasKeychainColumn) {
				$bindings['weapon_keychain'] = $keychainValue;
			}

			$existing = $db->select("SELECT `weapon_defindex` FROM `wp_player_skins`
				WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
				"steamid" => $steamid,
				"weapon_defindex" => $defindex,
				"team" => $targetTeam,
			]);

			if ($existing) {
				$db->query("UPDATE `wp_player_skins` SET " . implode(', ', $assignments) . "
					WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", $bindings);
			} else {
				$db->query("INSERT INTO `wp_player_skins` (" . implode(', ', $insertColumns) . ")
					VALUES (" . implode(', ', $insertValues) . ")", $bindings);
			}

			saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint, $wear, $seed, $stattrak, $stattrakCount, $nameTag);
			$updated = true;
		}

		if (!$updated) {
			go("{$fallbackUrl}&error=inspect_failed");
		}
		go("{$fallbackUrl}&imported=1");
	}

	if ($postAction === 'save_skin') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$displayTeam = readTeam($team);
		$preset = findPreset($db, $presetTable, $id);
		if (!$preset || !canEditPreset($preset)) {
			go('index.php?action=list');
		}

		$steamid = $preset['steamid'];
		$weapons = UtilsClass::getWeaponsFromArray();
		$skins = UtilsClass::skinsFromJson();
		$knifes = UtilsClass::getKnifeTypes();
		$gloves = glovesFromJson();
		$stickers = stickersFromJson();
		$keychains = keychainsFromJson();
		$hasKeychainColumn = keychainColumnAvailable($db);
		$selectedRows = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`
			FROM `wp_player_skins`
			WHERE `steamid` = :steamid AND `weapon_team` = :team", [
			"steamid" => $steamid,
			"team" => $displayTeam,
		]);
		$selectedSkins = UtilsClass::getSelectedSkins($selectedRows);

		$ex = explode("-", (string)($_POST['skin_forma'] ?? $_POST['forma'] ?? ''));
		if (($ex[0] ?? '') === 'knife' && isset($ex[1]) && array_key_exists((int)$ex[1], $knifes)) {
			$knifeKey = (int)$ex[1];
			$knifeDefindexes = knifeDefindexes($knifes);
			foreach (writeTeams($team) as $targetTeam) {
				$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`)
					VALUES(:steamid, :knife, :team)
					ON DUPLICATE KEY UPDATE `knife` = :knife_update", [
					"steamid" => $steamid,
					"knife" => $knifes[$knifeKey]['weapon_name'],
					"team" => $targetTeam,
					"knife_update" => $knifes[$knifeKey]['weapon_name'],
				]);

				if ($knifeKey === 0 && $knifeDefindexes) {
					$placeholders = [];
					$bindings = [
						"steamid" => $steamid,
						"team" => $targetTeam,
					];
					foreach ($knifeDefindexes as $index => $defindex) {
						$param = "knife_defindex_{$index}";
						$placeholders[] = ":{$param}";
						$bindings[$param] = $defindex;
					}
					$db->query("DELETE FROM `wp_player_skins`
						WHERE `steamid` = :steamid AND `weapon_team` = :team AND `weapon_defindex` IN (" . implode(',', $placeholders) . ")", $bindings);
				}
			}
		} elseif (($ex[0] ?? '') === 'glove' && isset($ex[1]) && array_key_exists((int)$ex[1], $gloves)) {
			$gloveDefindex = (int)$ex[1];
			$gloveDefindexes = gloveDefindexes($gloves);
			if (tableExists($db, 'wp_player_gloves')) {
				foreach (writeTeams($team) as $targetTeam) {
					if ($gloveDefindex === 0) {
						$db->query("DELETE FROM `wp_player_gloves`
							WHERE `steamid` = :steamid AND `weapon_team` = :team", [
							"steamid" => $steamid,
							"team" => $targetTeam,
						]);

						if ($gloveDefindexes) {
							$placeholders = [];
							$bindings = [
								"steamid" => $steamid,
								"team" => $targetTeam,
							];
							foreach ($gloveDefindexes as $index => $defindex) {
								$param = "glove_defindex_{$index}";
								$placeholders[] = ":{$param}";
								$bindings[$param] = $defindex;
							}
							$db->query("DELETE FROM `wp_player_skins`
								WHERE `steamid` = :steamid AND `weapon_team` = :team AND `weapon_defindex` IN (" . implode(',', $placeholders) . ")", $bindings);
						}
						continue;
					}

					$db->query("INSERT INTO `wp_player_gloves` (`steamid`, `weapon_team`, `weapon_defindex`)
						VALUES (:steamid, :team, :weapon_defindex)
						ON DUPLICATE KEY UPDATE `weapon_defindex` = :weapon_defindex_update", [
						"steamid" => $steamid,
						"team" => $targetTeam,
						"weapon_defindex" => $gloveDefindex,
						"weapon_defindex_update" => $gloveDefindex,
					]);

					$paint = (int)array_key_first($gloves[$gloveDefindex]);
					$existing = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4` FROM `wp_player_skins`
						WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
						"steamid" => $steamid,
						"weapon_defindex" => $gloveDefindex,
						"team" => $targetTeam,
					]);
					if ($existing) {
						$current = $existing[0];
						saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, (int)$current['weapon_paint_id'], (float)$current['weapon_wear'], (int)$current['weapon_seed'], 0, 0, null);
					}

					$cached = loadSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, $paint);
					$wear = $cached ? (float)$cached['weapon_wear'] : 0.0;
					$seed = $cached ? (int)$cached['weapon_seed'] : 0;

					if ($existing) {
						$db->query("UPDATE `wp_player_skins`
							SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = 0, `weapon_stattrak_count` = 0, `weapon_nametag` = NULL
							WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
							"steamid" => $steamid,
							"weapon_defindex" => $gloveDefindex,
							"weapon_paint_id" => $paint,
							"weapon_wear" => $wear,
							"weapon_seed" => $seed,
							"team" => $targetTeam,
						]);
					} else {
						$db->query("INSERT INTO `wp_player_skins`
							(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_team`)
							VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 0, 0, NULL, '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', :team)", [
							"steamid" => $steamid,
							"weapon_defindex" => $gloveDefindex,
							"weapon_paint_id" => $paint,
							"weapon_wear" => $wear,
							"weapon_seed" => $seed,
							"team" => $targetTeam,
						]);
					}
					saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, $paint, $wear, $seed, 0, 0, null);
				}
			}
		} elseif (($ex[0] ?? '') === 'gloveskin' && isset($ex[1], $ex[2]) && array_key_exists((int)$ex[1], $gloves) && array_key_exists((int)$ex[2], $gloves[(int)$ex[1]] ?? [])) {
			$defindex = (int)$ex[1];
			$paint = (int)$ex[2];
			$hasExplicitWear = array_key_exists('wear', $_POST);
			$hasExplicitSeed = array_key_exists('seed', $_POST);
			$hasExplicitSettings = $hasExplicitWear || $hasExplicitSeed;
			$submittedWear = $hasExplicitWear ? max(0.0, min(1.0, (float)$_POST['wear'])) : null;
			$submittedSeed = $hasExplicitSeed ? max(0, min(1000, (int)$_POST['seed'])) : null;

			foreach (writeTeams($team) as $targetTeam) {
				if (tableExists($db, 'wp_player_gloves')) {
					$db->query("INSERT INTO `wp_player_gloves` (`steamid`, `weapon_team`, `weapon_defindex`)
						VALUES (:steamid, :team, :weapon_defindex)
						ON DUPLICATE KEY UPDATE `weapon_defindex` = :weapon_defindex_update", [
						"steamid" => $steamid,
						"team" => $targetTeam,
						"weapon_defindex" => $defindex,
						"weapon_defindex_update" => $defindex,
					]);
				}

				$existing = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4` FROM `wp_player_skins`
					WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
					"steamid" => $steamid,
					"weapon_defindex" => $defindex,
					"team" => $targetTeam,
				]);
				if ($existing) {
					$current = $existing[0];
					saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, (int)$current['weapon_paint_id'], (float)$current['weapon_wear'], (int)$current['weapon_seed'], 0, 0, null);
				}

				if ($hasExplicitSettings) {
					$wear = $submittedWear ?? ($existing[0]['weapon_wear'] ?? 0.0);
					$seed = $submittedSeed ?? ($existing[0]['weapon_seed'] ?? 0);
				} else {
					$cached = loadSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint);
					$wear = $cached ? (float)$cached['weapon_wear'] : 0.0;
					$seed = $cached ? (int)$cached['weapon_seed'] : 0;
				}

				if ($existing) {
					$db->query("UPDATE `wp_player_skins`
						SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = 0, `weapon_stattrak_count` = 0, `weapon_nametag` = NULL
						WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
						"steamid" => $steamid,
						"weapon_defindex" => $defindex,
						"weapon_paint_id" => $paint,
						"weapon_wear" => $wear,
						"weapon_seed" => $seed,
						"team" => $targetTeam,
					]);
				} else {
					$db->query("INSERT INTO `wp_player_skins`
						(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_team`)
						VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 0, 0, NULL, '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', :team)", [
						"steamid" => $steamid,
						"weapon_defindex" => $defindex,
						"weapon_paint_id" => $paint,
						"weapon_wear" => $wear,
						"weapon_seed" => $seed,
						"team" => $targetTeam,
					]);
				}
				saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint, $wear, $seed, 0, 0, null);
			}

		} elseif (isset($ex[0], $ex[1]) && array_key_exists((int)$ex[0], $weapons) && array_key_exists((int)$ex[1], $skins[(int)$ex[0]] ?? [])) {
			$defindex = (int)$ex[0];
			$paint = (int)$ex[1];
			$hasExplicitWear = array_key_exists('wear', $_POST);
			$hasExplicitSeed = array_key_exists('seed', $_POST);
			$submittedStickerValues = readStickerValuesFromPost(stickerSlotCount($defindex), $stickers);
			$submittedKeychainValue = $hasKeychainColumn ? readKeychainValueFromPost($keychains) : null;
			$hasExplicitSettings = $hasExplicitWear || $hasExplicitSeed || array_key_exists('stattrak', $_POST) || array_key_exists('nametag_present', $_POST) || $submittedStickerValues !== null || $submittedKeychainValue !== null;
			$submittedWear = $hasExplicitWear ? max(0.0, min(1.0, (float)$_POST['wear'])) : null;
			$submittedSeed = $hasExplicitSeed ? max(0, min(1000, (int)$_POST['seed'])) : null;
			$submittedStatTrak = array_key_exists('stattrak', $_POST) ? 1 : 0;
			$submittedStatTrakCount = $submittedStatTrak ? max(0, min(999999, (int)($_POST['weapon_stattrak_count'] ?? 0))) : 0;
			$submittedNameTag = readNameTagFromPost();
			if ($submittedNameTag === false) {
				go("index.php?action=edit&id={$id}&team={$team}&error=nametag");
			}
			$isKnifeSkin = in_array($defindex, knifeDefindexes($knifes), true);

			foreach (writeTeams($team) as $targetTeam) {
				if ($isKnifeSkin) {
					$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`)
						VALUES(:steamid, :knife, :team)
						ON DUPLICATE KEY UPDATE `knife` = :knife_update", [
						"steamid" => $steamid,
						"knife" => $knifes[$defindex]['weapon_name'],
						"team" => $targetTeam,
						"knife_update" => $knifes[$defindex]['weapon_name'],
					]);
				}

				$existing = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4` FROM `wp_player_skins`
					WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
					"steamid" => $steamid,
					"weapon_defindex" => $defindex,
					"team" => $targetTeam,
				]);
				if ($existing) {
					$current = $existing[0];
					saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, (int)$current['weapon_paint_id'], (float)$current['weapon_wear'], (int)$current['weapon_seed'], (int)$current['weapon_stattrak'], (int)($current['weapon_stattrak_count'] ?? 0), $current['weapon_nametag']);
				}

				if ($hasExplicitSettings) {
					$wear = $submittedWear ?? ($existing[0]['weapon_wear'] ?? 0.0);
					$seed = $submittedSeed ?? ($existing[0]['weapon_seed'] ?? 0);
					$stattrak = $submittedStatTrak;
					$stattrakCount = $stattrak ? $submittedStatTrakCount : 0;
					$nameTag = array_key_exists('nametag_present', $_POST) ? $submittedNameTag : ($existing[0]['weapon_nametag'] ?? null);
					$stickerValues = $submittedStickerValues ?? ($existing ? stickerValuesFromRow($existing[0]) : defaultStickerValues());
				} else {
					$cached = loadSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint);
					$wear = $cached ? (float)$cached['weapon_wear'] : 0.0;
					$seed = $cached ? (int)$cached['weapon_seed'] : 0;
					$stattrak = $cached ? (int)$cached['weapon_stattrak'] : 0;
					$stattrakCount = $stattrak && $cached ? (int)($cached['weapon_stattrak_count'] ?? 0) : 0;
					$nameTag = $cached ? $cached['weapon_nametag'] : null;
					$stickerValues = $existing ? stickerValuesFromRow($existing[0]) : defaultStickerValues();
				}

				// La colonne charm n'est touchée que si le formulaire l'a envoyée,
				// pour ne pas l'effacer lors d'un simple changement de skin.
				$keychainAssignment = '';
				$keychainInsertColumn = '';
				$keychainInsertValue = '';
				$keychainBinding = [];
				if ($hasKeychainColumn && $submittedKeychainValue !== null) {
					$keychainAssignment = ', `weapon_keychain` = :weapon_keychain';
					$keychainInsertColumn = ', `weapon_keychain`';
					$keychainInsertValue = ', :weapon_keychain';
					$keychainBinding = ["weapon_keychain" => $submittedKeychainValue];
				}

				if ($existing) {
					$db->query("UPDATE `wp_player_skins`
						SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = :weapon_stattrak, `weapon_stattrak_count` = :weapon_stattrak_count, `weapon_nametag` = :weapon_nametag, `weapon_sticker_0` = :weapon_sticker_0, `weapon_sticker_1` = :weapon_sticker_1, `weapon_sticker_2` = :weapon_sticker_2, `weapon_sticker_3` = :weapon_sticker_3, `weapon_sticker_4` = :weapon_sticker_4{$keychainAssignment}
						WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
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
						"team" => $targetTeam,
					] + $keychainBinding);
				} else {
					$db->query("INSERT INTO `wp_player_skins`
						(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`{$keychainInsertColumn}, `weapon_team`)
						VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, :weapon_stattrak, :weapon_stattrak_count, :weapon_nametag, :weapon_sticker_0, :weapon_sticker_1, :weapon_sticker_2, :weapon_sticker_3, :weapon_sticker_4{$keychainInsertValue}, :team)", [
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
						"team" => $targetTeam,
					] + $keychainBinding);
				}
				saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint, $wear, $seed, $stattrak, $stattrakCount, $nameTag);
			}
		}

		go("index.php?action=edit&id={$id}&team={$team}");
	}

	if ($postAction === 'save_music') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		$music = musicFromJson();
		$musicId = (int)($_POST['music_id'] ?? 0);
		if (!$preset || !canEditPreset($preset) || $team !== 1 || !tableExists($db, 'wp_player_music') || !array_key_exists($musicId, $music)) {
			go("index.php?action=edit&id={$id}&team={$team}");
		}

		foreach (writeTeams($team) as $targetTeam) {
			if ($musicId === 0) {
				$db->query("DELETE FROM `wp_player_music` WHERE `steamid` = :steamid AND `weapon_team` = :team", [
					"steamid" => $preset['steamid'],
					"team" => $targetTeam,
				]);
				continue;
			}
			$db->query("INSERT INTO `wp_player_music` (`steamid`, `weapon_team`, `music_id`)
				VALUES (:steamid, :team, :music_id)
				ON DUPLICATE KEY UPDATE `music_id` = :music_id_update", [
				"steamid" => $preset['steamid'],
				"team" => $targetTeam,
				"music_id" => $musicId,
				"music_id_update" => $musicId,
			]);
		}

		go("index.php?action=edit&id={$id}&team={$team}");
	}

	if ($postAction === 'save_pin') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		$pins = pinsFromJson();
		$pinId = (int)($_POST['pin_id'] ?? 0);
		if (!$preset || !canEditPreset($preset) || !tableExists($db, 'wp_player_pins') || !array_key_exists($pinId, $pins)) {
			go("index.php?action=edit&id={$id}&team={$team}");
		}

		foreach (writeTeams($team) as $targetTeam) {
			if ($pinId === 0) {
				$db->query("DELETE FROM `wp_player_pins` WHERE `steamid` = :steamid AND `weapon_team` = :team", [
					"steamid" => $preset['steamid'],
					"team" => $targetTeam,
				]);
				continue;
			}
			$db->query("INSERT INTO `wp_player_pins` (`steamid`, `weapon_team`, `id`)
				VALUES (:steamid, :team, :pin_id)
				ON DUPLICATE KEY UPDATE `id` = :pin_id_update", [
				"steamid" => $preset['steamid'],
				"team" => $targetTeam,
				"pin_id" => $pinId,
				"pin_id_update" => $pinId,
			]);
		}

		go("index.php?action=edit&id={$id}&team={$team}");
	}

	if ($postAction === 'save_agent') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		if (!$preset || !canEditPreset($preset) || !in_array($team, [2, 3], true) || !tableExists($db, 'wp_player_agents')) {
			go("index.php?action=edit&id={$id}&team={$team}");
		}

		$agentColumn = $team === 2 ? 'agent_t' : 'agent_ct';
		$agentModel = trim((string)($_POST['agent_model'] ?? ''));
		if ($agentModel === 'null') {
			$agentModel = '';
		}
		$agentValue = $agentModel === '' ? null : $agentModel;
		$db->query("INSERT INTO `wp_player_agents` (`steamid`, `{$agentColumn}`)
			VALUES (:steamid, :agent_model)
			ON DUPLICATE KEY UPDATE `{$agentColumn}` = :agent_model_update", [
			"steamid" => $preset['steamid'],
			"agent_model" => $agentValue,
			"agent_model_update" => $agentValue,
		]);

		go("index.php?action=edit&id={$id}&team={$team}");
	}
}

$team = selectedTeam();
$currentPreset = null;
$weapons = [];
$skins = [];
$selectedSkins = [];
$selectedKnife = null;
$knifes = [];
$agents = [];
$selectedAgent = null;
$gloves = [];
$selectedGlove = null;
$stickers = [];
$music = [];
$selectedMusic = null;
$pins = [];
$selectedPin = null;

if ($action === 'edit') {
	$id = cleanSteamId($_GET['id'] ?? '');
	$displayTeam = readTeam($team);
	$currentPreset = findPreset($db, $presetTable, $id);
	if (!$currentPreset) {
		go('index.php?action=list');
	}
	if (!canEditPreset($currentPreset)) {
		go('index.php?action=list&loadout_password_required=' . rawurlencode($currentPreset['steamid']) . '&loadout_password_team=' . $team);
	}

	$steamid = $currentPreset['steamid'];
	$weapons = UtilsClass::getWeaponsFromArray();
	$skins = UtilsClass::skinsFromJson();
	$gloves = glovesFromJson();
	$stickers = stickersFromJson();
	$music = musicFromJson();
	$pins = pinsFromJson();
	$keychains = keychainsFromJson();
	$hasKeychainColumn = keychainColumnAvailable($db);
	$keychainSelect = $hasKeychainColumn ? ', `weapon_keychain`' : '';
	$selectedRows = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`{$keychainSelect}
		FROM `wp_player_skins`
		WHERE `steamid` = :steamid AND `weapon_team` = :team", [
		"steamid" => $steamid,
		"team" => $displayTeam,
	]);
	$selectedSkins = UtilsClass::getSelectedSkins($selectedRows);
	// getSelectedSkins tronque la ligne : on garde les lignes brutes pour l'encodage
	// du lien d'inspection, qui a besoin du compteur StatTrak et du charm.
	$selectedRowsByDefindex = [];
	foreach ($selectedRows as $selectedRow) {
		$selectedRowsByDefindex[(int)$selectedRow['weapon_defindex']] = $selectedRow;
	}
	$selectedKnifeRows = $db->select("SELECT * FROM `wp_player_knife` WHERE `steamid` = :steamid AND `weapon_team` = :team LIMIT 1", [
		"steamid" => $steamid,
		"team" => $displayTeam,
	]);
	$selectedKnife = $selectedKnifeRows[0] ?? null;
	$knifes = UtilsClass::getKnifeTypes();
	if (tableExists($db, 'wp_player_gloves')) {
		$selectedGloveRows = $db->select("SELECT `weapon_defindex` FROM `wp_player_gloves` WHERE `steamid` = :steamid AND `weapon_team` = :team LIMIT 1", [
			"steamid" => $steamid,
			"team" => $displayTeam,
		]);
		$selectedGlove = $selectedGloveRows[0] ?? null;
	}
	if (tableExists($db, 'wp_player_music')) {
		$selectedMusicRows = $db->select("SELECT `music_id` FROM `wp_player_music` WHERE `steamid` = :steamid AND `weapon_team` = :team LIMIT 1", [
			"steamid" => $steamid,
			"team" => $displayTeam,
		]);
		$selectedMusic = isset($selectedMusicRows[0]['music_id']) ? (int)$selectedMusicRows[0]['music_id'] : null;
	}
	if (tableExists($db, 'wp_player_pins')) {
		$selectedPinRows = $db->select("SELECT `id` FROM `wp_player_pins` WHERE `steamid` = :steamid AND `weapon_team` = :team LIMIT 1", [
			"steamid" => $steamid,
			"team" => $displayTeam,
		]);
		$selectedPin = isset($selectedPinRows[0]['id']) ? (int)$selectedPinRows[0]['id'] : null;
	}
	$agents = agentsFromJson();
	if (in_array($team, [2, 3], true) && tableExists($db, 'wp_player_agents')) {
		$agentColumn = $team === 2 ? 'agent_t' : 'agent_ct';
		$selectedAgentRows = $db->select("SELECT `{$agentColumn}` AS `agent_model` FROM `wp_player_agents` WHERE `steamid` = :steamid LIMIT 1", [
			"steamid" => $steamid,
		]);
		$selectedAgent = trim((string)($selectedAgentRows[0]['agent_model'] ?? ''));
		if ($selectedAgent === 'null') {
			$selectedAgent = '';
		}
	}
}

$presets = $accessGranted ? $db->select("SELECT * FROM `{$presetTable}` ORDER BY `created_time` ASC, `id` ASC") : [];
$returnTo = 'index.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
$returnTo = safeReturnUrl($returnTo);
?>
<!DOCTYPE html>
<html lang="<?= h($currentLanguage) ?>" data-bs-theme="dark">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<link rel="icon" type="image/svg+xml" href="favicon.svg">
	<link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
	<title><?= h(t('app_title')) ?></title>
</head>

<body>
	<main class="app-shell<?= $action === 'access' ? ' access-shell' : '' ?>">
		<?php if ($action === 'access') : ?>
			<section class="access-panel panel narrow">
				<h1><?= h(t('access_title')) ?></h1>
				<p class="hint"><?= h(t('access_prompt')) ?></p>
				<?php if ($accessError) : ?><div class="alert alert-danger"><?= h(t('access_invalid')) ?></div><?php endif; ?>
				<form method="post" class="form-grid">
					<input type="hidden" name="action" value="verify_access">
					<label><?= h(t('access_password')) ?>
						<input class="form-control" type="password" name="access_password" autocomplete="current-password" required autofocus>
					</label>
					<button class="btn btn-primary" type="submit"><?= h(t('access_unlock')) ?></button>
				</form>
			</section>
		<?php elseif ($action === 'home') : ?>
			<section class="home-panel">
				<h1><?= h(t('app_title')) ?></h1>
				<p><?= h(t('home_subtitle')) ?></p>
				<div class="home-actions">
					<a class="btn btn-primary btn-lg" href="index.php?action=list"><?= h(t('select_preset')) ?></a>
					<a class="btn btn-outline-light btn-lg" href="index.php?action=new"><?= h(t('new_preset')) ?></a>
				</div>
			</section>
		<?php elseif ($action === 'new') : ?>
			<a class="back-link" href="index.php"><?= h(t('back_home')) ?></a>
			<section class="panel loadout-info-panel create-loadout-panel">
				<div class="identity-panel-head">
					<div>
						<h1><?= h(t('new_preset')) ?></h1>
						<p><?= h(t('basic_info')) ?></p>
					</div>
					<span class="identity-status" data-loadout-password-status data-enabled-label="<?= h(t('loadout_password_enabled')) ?>" data-disabled-label="<?= h(t('loadout_password_disabled')) ?>"><?= h(t('loadout_password_disabled')) ?></span>
				</div>
				<?php if ($error) : ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
				<form method="post" class="identity-form loadout-info-form">
					<input type="hidden" name="action" value="create_preset">
					<div class="identity-main-fields">
						<label>Steam64 ID
							<input class="form-control" name="steamid" value="<?= h($_POST['steamid'] ?? '') ?>" inputmode="numeric" autocomplete="off" required>
						</label>
						<label><?= h(t('nickname')) ?>
							<input class="form-control" name="nickname" value="<?= h($_POST['nickname'] ?? '') ?>" autocomplete="off" placeholder="<?= h(t('nickname_placeholder')) ?>">
						</label>
					</div>
					<div class="identity-loadout-password-settings">
						<div class="loadout-password-setting-copy">
							<strong><?= h(t('loadout_password_protection')) ?></strong>
							<small><?= h(t('loadout_password_optional_hint')) ?></small>
						</div>
						<label class="loadout-password-toggle form-check form-switch">
							<input class="form-check-input" type="checkbox" role="switch" name="enable_loadout_password" value="1" data-loadout-password-toggle <?= isset($_POST['enable_loadout_password']) ? 'checked' : '' ?>>
							<span><?= h(t('enable_loadout_password')) ?></span>
						</label>
						<label class="loadout-password-input-wrap<?= isset($_POST['enable_loadout_password']) ? '' : ' d-none' ?>" data-loadout-password-input-wrap>
							<span class="visually-hidden"><?= h(t('enter_loadout_password')) ?></span>
							<input class="form-control" type="password" name="loadout_password" autocomplete="one-time-code" placeholder="<?= h(t('loadout_password_set_placeholder')) ?>" data-loadout-password-input data-loadout-password-required-when-enabled>
						</label>
					</div>
					<div class="identity-form-actions">
						<button class="btn btn-primary" type="submit"><?= h(t('create')) ?></button>
					</div>
				</form>
			</section>
		<?php elseif ($action === 'list') : ?>
			<header class="page-head">
				<div>
					<a class="back-link" href="index.php"><?= h(t('back_home')) ?></a>
					<h1><?= h(t('select_preset')) ?></h1>
				</div>
				<a class="btn btn-primary" href="index.php?action=new"><?= h(t('new_preset')) ?></a>
			</header>

			<?php if (($_GET['notice'] ?? '') === 'updated_existing') : ?>
				<div class="alert alert-info"><?= h(t('updated_notice')) ?></div>
			<?php endif; ?>

			<?php if (!$presets) : ?>
				<section class="panel"><?= h(t('empty_presets')) ?></section>
			<?php endif; ?>

			<div class="preset-list">
				<?php foreach ($presets as $preset) : ?>
					<article class="preset-card">
						<div class="preset-card-body">
							<strong><?= h(presetLabel($preset)) ?></strong>
							<span><?= h($preset['steamid']) ?></span>
							<?php if (loadoutHasPassword($preset)) : ?>
								<div class="loadout-password-label" title="<?= h(t('loadout_password_enabled')) ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
									<?= h(t('loadout_password_label')) ?>
								</div>
							<?php endif; ?>
						</div>
						<div class="preset-actions">
							<?php if (canEditPreset($preset)) : ?>
								<a class="btn btn-outline-light" href="<?= h(editUrl($preset, 1)) ?>"><?= h(t('edit')) ?></a>
							<?php else : ?>
								<button class="btn btn-outline-light" type="button" data-bs-toggle="modal" data-bs-target="#loadoutPasswordModal" data-loadout-password-id="<?= h($preset['steamid']) ?>" data-loadout-password-label="<?= h(presetLabel($preset)) ?>" data-loadout-password-team="1"><?= h(t('edit')) ?></button>
							<?php endif; ?>
							<form method="post" onsubmit="return confirm(<?= h(json_encode(t('delete_confirm'), JSON_UNESCAPED_UNICODE)) ?>);">
								<input type="hidden" name="action" value="delete_preset">
								<input type="hidden" name="id" value="<?= h($preset['steamid']) ?>">
								<button class="btn btn-outline-danger" type="submit" <?= canEditPreset($preset) ? '' : 'disabled' ?>><?= h(t('delete')) ?></button>
							</form>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php elseif ($action === 'edit' && $currentPreset) : ?>
			<header class="page-head">
				<div>
					<a class="back-link" href="index.php?action=list"><?= h(t('back_list')) ?></a>
					<h1><?= h(t('edit_preset')) ?></h1>
					<p><?= h(presetLabel($currentPreset)) ?> · <?= h($teams[$team]) ?></p>
				</div>
				<nav class="team-tabs">
					<a class="<?= $team === 1 ? 'active' : '' ?>" href="index.php?action=edit&id=<?= h($currentPreset['steamid']) ?>&team=1"><?= h($teams[1]) ?></a>
					<a class="<?= $team === 2 ? 'active' : '' ?>" href="index.php?action=edit&id=<?= h($currentPreset['steamid']) ?>&team=2"><?= h($teams[2]) ?></a>
					<a class="<?= $team === 3 ? 'active' : '' ?>" href="index.php?action=edit&id=<?= h($currentPreset['steamid']) ?>&team=3"><?= h($teams[3]) ?></a>
				</nav>
			</header>

			<?php if (isset($_GET['saved'])) : ?><div class="alert alert-success"><?= h(t('saved_notice')) ?></div><?php endif; ?>
			<?php if (isset($_GET['imported'])) : ?><div class="alert alert-success"><?= h(t('inspect_imported')) ?></div><?php endif; ?>
			<?php if (($_GET['error'] ?? '') === 'loadout_password') : ?>
				<div class="alert alert-danger"><?= h(t('loadout_password_required')) ?></div>
			<?php elseif (strpos((string)($_GET['error'] ?? ''), 'inspect_') === 0) : ?>
				<?php $inspectErrorKey = 'inspect_error_' . substr((string)$_GET['error'], strlen('inspect_')); ?>
				<div class="alert alert-danger"><?= h(t($inspectErrorKey) !== $inspectErrorKey ? t($inspectErrorKey) : t('inspect_error_failed')) ?></div>
			<?php elseif (isset($_GET['error'])) : ?>
				<div class="alert alert-danger"><?= h(t('save_failed')) ?></div>
			<?php endif; ?>

			<section class="panel loadout-info-panel">
				<div class="identity-panel-head">
					<div>
						<h2><?= h(t('basic_info')) ?></h2>
					</div>
					<span class="identity-status<?= loadoutHasPassword($currentPreset) ? ' active' : '' ?>" data-loadout-password-status data-enabled-label="<?= h(t('loadout_password_enabled')) ?>" data-disabled-label="<?= h(t('loadout_password_disabled')) ?>"><?= h(loadoutHasPassword($currentPreset) ? t('loadout_password_enabled') : t('loadout_password_disabled')) ?></span>
				</div>
				<form method="post" class="identity-form loadout-info-form">
					<input type="hidden" name="action" value="save_identity">
					<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
					<input type="hidden" name="team" value="<?= $team ?>">
					<div class="identity-main-fields">
						<label>Steam64 ID
							<input class="form-control" name="steamid" value="<?= h($currentPreset['steamid']) ?>" inputmode="numeric" required>
						</label>
						<label><?= h(t('nickname')) ?>
							<input class="form-control" name="nickname" value="<?= h($currentPreset['nickname'] ?? '') ?>">
						</label>
					</div>
					<div class="identity-loadout-password-settings">
						<div class="loadout-password-setting-copy">
							<strong><?= h(t('loadout_password_protection')) ?></strong>
							<small><?= h(t('loadout_password_optional_hint')) ?></small>
						</div>
						<label class="loadout-password-toggle form-check form-switch">
							<input class="form-check-input" type="checkbox" role="switch" name="enable_loadout_password" value="1" data-loadout-password-toggle <?= loadoutHasPassword($currentPreset) ? 'checked' : '' ?>>
							<span><?= h(t('enable_loadout_password')) ?></span>
						</label>
						<label class="loadout-password-input-wrap<?= loadoutHasPassword($currentPreset) ? '' : ' d-none' ?>" data-loadout-password-input-wrap>
							<span class="visually-hidden"><?= h(t('enter_loadout_password')) ?></span>
							<input class="form-control" type="password" name="loadout_password" autocomplete="one-time-code" placeholder="<?= h(loadoutHasPassword($currentPreset) ? t('loadout_password_change_placeholder') : t('loadout_password_set_placeholder')) ?>" data-loadout-password-input <?= loadoutHasPassword($currentPreset) ? '' : 'data-loadout-password-required-when-enabled' ?>>
						</label>
					</div>
					<div class="identity-form-actions">
						<button class="btn btn-primary" type="submit"><?= h(t('save')) ?></button>
					</div>
				</form>
			</section>

			<div class="card-grid">
				<div class="skin-card featured loadout-card">
					<?php
					$actualKnife = $knifes[0];
					$actualKnifeKey = 0;
					if ($selectedKnife !== null) {
						foreach ($knifes as $knifeKey => $knife) {
							if ($selectedKnife['knife'] === $knife['weapon_name']) {
								$actualKnife = $knife;
								$actualKnifeKey = (int)$knifeKey;
								break;
							}
						}
					}
					$knifeSkinOptions = $actualKnifeKey > 0 ? ($skins[$actualKnifeKey] ?? []) : [];
					$selectedKnifeSkin = $selectedSkins[$actualKnifeKey] ?? null;
					$currentKnifePaintId = $selectedKnifeSkin ? (int)$selectedKnifeSkin['weapon_paint_id'] : 0;
					$currentKnifeSkin = $actualKnifeKey > 0 && isset($knifeSkinOptions[$currentKnifePaintId]) ? $knifeSkinOptions[$currentKnifePaintId] : $actualKnife;
					$currentKnifeWear = $selectedKnifeSkin['weapon_wear'] ?? 0.0;
					$currentKnifeSeed = $selectedKnifeSkin['weapon_seed'] ?? 0;
					$currentKnifeStatTrak = (int)($selectedKnifeSkin['weapon_stattrak'] ?? 0);
					$currentKnifeStatTrakCount = $currentKnifeStatTrak ? (int)($selectedKnifeSkin['weapon_stattrak_count'] ?? 0) : 0;
					$currentKnifeNameTag = $selectedKnifeSkin['weapon_nametag'] ?? null;
					if ($actualKnifeKey > 0) {
						$cachedKnifeSetting = loadSkinSettingCache($db, $skinSettingsTable, $currentPreset['steamid'], $displayTeam, $actualKnifeKey, $currentKnifePaintId);
						if ($cachedKnifeSetting) {
							$currentKnifeWear = $cachedKnifeSetting['weapon_wear'];
							$currentKnifeSeed = $cachedKnifeSetting['weapon_seed'];
							$currentKnifeStatTrak = (int)$cachedKnifeSetting['weapon_stattrak'];
							$currentKnifeStatTrakCount = $currentKnifeStatTrak ? (int)($cachedKnifeSetting['weapon_stattrak_count'] ?? 0) : 0;
							$currentKnifeNameTag = $cachedKnifeSetting['weapon_nametag'];
						}
					}
					$currentKnifeNameTagEnabled = $currentKnifeNameTag !== null && $currentKnifeNameTag !== '';
					?>
					<?php if ($currentKnifeNameTagEnabled || $currentKnifeStatTrak) : ?>
						<div class="card-status-badges">
							<?php if ($currentKnifeNameTagEnabled) : ?><span class="nametag-badge"><?= h(t('name_tag')) ?></span><?php endif; ?>
							<?php if ($currentKnifeStatTrak) : ?><span class="stattrak-badge">StatTrak™</span><?php endif; ?>
						</div>
					<?php endif; ?>
					<div class="card-title-wrap">
						<span><?= h(t('knife_type')) ?></span>
						<h2><?= h($currentKnifeSkin['paint_name']) ?></h2>
					</div>
					<?php $knifePlaceholder = weaponPlaceholderImage($actualKnife['weapon_name'] ?? ''); ?>
					<?php if ($knifePlaceholder !== '') : ?>
						<img src="<?= h($knifePlaceholder) ?>" data-remote-src="<?= h($currentKnifeSkin['image_url'] ?? '') ?>" class="skin-image" alt="">
					<?php else : ?>
						<img src="<?= h($currentKnifeSkin['image_url']) ?>" class="skin-image" alt="">
					<?php endif; ?>
					<div class="skin-meta">
						<span><?= h(t('wear_value')) ?> <?= h($currentKnifeWear) ?></span>
						<span><?= h(t('pattern')) ?> <?= h($currentKnifeSeed) ?></span>
					</div>
					<div class="settings-row">
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#knifeTypeModal">
							<?= h(t('choose_type')) ?>
						</button>
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#knifeSkinModal" <?= $actualKnifeKey === 0 ? 'disabled' : '' ?>>
							<?= h(t('choose_skin')) ?>
						</button>
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#knifeModal" <?= $actualKnifeKey === 0 ? 'disabled' : '' ?>>
							<?= h(t('edit')) ?>
						</button>
					</div>

					<form method="post" class="modal-form">
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="modal fade skin-picker-modal" id="knifeTypeModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_type_title')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<div class="skin-picker-grid">
											<?php foreach ($knifes as $knifeKey => $knife) : ?>
												<?php
												$knifeTypePlaceholder = weaponPlaceholderImage($knife['weapon_name'] ?? '');
												$knifeTypeImage = (string)($knife['image_url'] ?? '');
												?>
												<button type="submit" name="forma" value="knife-<?= (int)$knifeKey ?>" class="skin-result <?= $actualKnifeKey === (int)$knifeKey ? 'active' : '' ?>">
													<?php if ($knifeTypePlaceholder !== '') : ?>
														<img src="<?= h($knifeTypePlaceholder) ?>" data-picker-remote-src="<?= h($knifeTypeImage) ?>" alt="">
													<?php elseif ($knifeTypeImage !== '') : ?>
														<img src="<?= h($knifeTypeImage) ?>" alt="">
													<?php else : ?>
														<div class="empty-image"><?= h($knife['paint_name']) ?></div>
													<?php endif; ?>
													<span><?= h($knife['paint_name']) ?></span>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>

					<form method="post" class="modal-form">
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="modal fade skin-picker-modal" id="knifeSkinModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_skin_title')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<?php if ($actualKnifeKey === 0) : ?>
											<p class="hint"><?= h(t('choose_knife_hint')) ?></p>
										<?php else : ?>
											<div class="skin-picker-grid">
												<?php foreach ($knifeSkinOptions as $paintKey => $paint) : ?>
													<?php $knifeSkinImage = (string)($paint['image_url'] ?? ''); ?>
													<button type="submit" name="skin_forma" value="<?= (int)$actualKnifeKey ?>-<?= (int)$paintKey ?>" class="skin-result <?= $currentKnifePaintId === (int)$paintKey ? 'active' : '' ?>">
														<?php if ($knifePlaceholder !== '') : ?>
															<img src="<?= h($knifePlaceholder) ?>" data-picker-remote-src="<?= h($knifeSkinImage) ?>" alt="">
														<?php elseif ($knifeSkinImage !== '') : ?>
															<img src="<?= h($knifeSkinImage) ?>" alt="">
														<?php else : ?>
															<div class="empty-image"><?= h($paint['paint_name']) ?></div>
														<?php endif; ?>
														<span><?= h($paint['paint_name']) ?></span>
													</button>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</form>

					<form method="post">
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<input type="hidden" name="forma" value="<?= (int)$actualKnifeKey ?>-<?= (int)$currentKnifePaintId ?>">
						<div class="modal fade" id="knifeModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h($currentKnifeSkin['paint_name']) ?> <?= h(t('settings')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<?php if ($actualKnifeKey === 0) : ?>
											<p class="hint"><?= h(t('choose_knife_hint')) ?></p>
										<?php else : ?>
											<div class="row g-3">
												<div class="col-sm-6">
													<label><?= h(t('wear_value')) ?>
														<input type="number" step="any" min="0" max="1" value="<?= h($currentKnifeWear) ?>" class="form-control" name="wear">
													</label>
												</div>
												<div class="col-sm-6">
													<label><?= h(t('pattern')) ?>
														<input type="number" min="0" max="1000" value="<?= h($currentKnifeSeed) ?>" class="form-control" name="seed">
													</label>
												</div>
												<div class="col-12 nametag-row">
													<input type="hidden" name="nametag_present" value="1">
													<label class="check-line">
														<input type="checkbox" name="nametag_enabled" value="1" data-nametag-toggle <?= $currentKnifeNameTagEnabled ? 'checked' : '' ?>>
														<span class="nametag-label"><?= h(t('name_tag')) ?></span>
													</label>
													<input type="text" name="weapon_nametag" value="<?= h($currentKnifeNameTag ?? '') ?>" maxlength="20" class="form-control nametag-input" data-nametag-input <?= $currentKnifeNameTagEnabled ? '' : 'disabled hidden' ?>>
												</div>
												<div class="col-12 stattrak-row">
	<label class="check-line">
		<input type="checkbox" name="stattrak" value="1" data-stattrak-toggle <?= $currentKnifeStatTrak ? 'checked' : '' ?>>
		<span class="stattrak-label">StatTrak™</span>
	</label>
	<input type="number" name="weapon_stattrak_count" value="<?= h($currentKnifeStatTrakCount) ?>" min="0" max="999999" step="1" class="form-control stattrak-input" data-stattrak-input <?= $currentKnifeStatTrak ? '' : 'disabled hidden' ?>>
</div>
											</div>
										<?php endif; ?>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
										<?php if ($actualKnifeKey > 0) : ?><button type="submit" class="btn btn-primary"><?= h(t('save')) ?></button><?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>


				<?php
				$gloveTypes = gloveTypeOptions($gloves);
				$actualGloveDefindex = 0;
				if ($selectedGlove !== null && array_key_exists((int)$selectedGlove['weapon_defindex'], $gloves)) {
					$actualGloveDefindex = (int)$selectedGlove['weapon_defindex'];
				}
				$actualGlove = $gloveTypes[$actualGloveDefindex] ?? ($gloveTypes[0] ?? ['paint_name' => (UtilsClass::currentLanguage() === 'en' ? 'Use inventory gloves' : '使用库存手套'), 'image_url' => '']);
				$gloveSkinOptions = $actualGloveDefindex > 0 ? ($gloves[$actualGloveDefindex] ?? []) : [];
				$selectedGloveSkin = $actualGloveDefindex > 0 ? ($selectedSkins[$actualGloveDefindex] ?? null) : null;
				$currentGlovePaintId = $selectedGloveSkin ? (int)$selectedGloveSkin['weapon_paint_id'] : 0;
				if ($actualGloveDefindex > 0 && !isset($gloveSkinOptions[$currentGlovePaintId]) && $gloveSkinOptions) {
					$currentGlovePaintId = (int)array_key_first($gloveSkinOptions);
				}
				$currentGloveSkin = $actualGloveDefindex > 0 && isset($gloveSkinOptions[$currentGlovePaintId]) ? $gloveSkinOptions[$currentGlovePaintId] : $actualGlove;
				$currentGloveWear = $selectedGloveSkin['weapon_wear'] ?? 0.0;
				$currentGloveSeed = $selectedGloveSkin['weapon_seed'] ?? 0;
				if ($actualGloveDefindex > 0) {
					$cachedGloveSetting = loadSkinSettingCache($db, $skinSettingsTable, $currentPreset['steamid'], $displayTeam, $actualGloveDefindex, $currentGlovePaintId);
					if ($cachedGloveSetting) {
						$currentGloveWear = $cachedGloveSetting['weapon_wear'];
						$currentGloveSeed = $cachedGloveSetting['weapon_seed'];
					}
				}
				?>
				<div class="skin-card featured loadout-card">
					<div class="card-title-wrap">
						<span><?= h(t('glove_type')) ?></span>
						<h2><?= h($currentGloveSkin['paint_name']) ?></h2>
					</div>
					<?php $glovePlaceholder = glovePlaceholderImage($actualGloveDefindex); ?>
					<?php if ($glovePlaceholder !== '') : ?>
						<img src="<?= h($glovePlaceholder) ?>" data-remote-src="<?= h($currentGloveSkin['image_url'] ?? '') ?>" class="skin-image" alt="">
					<?php elseif (!empty($currentGloveSkin['image_url'])) : ?>
						<img src="<?= h($currentGloveSkin['image_url']) ?>" class="skin-image" alt="">
					<?php else : ?>
						<div class="empty-image"><?= h(t('default_gloves')) ?></div>
					<?php endif; ?>
					<div class="skin-meta">
						<span><?= h(t('wear_value')) ?> <?= h($currentGloveWear) ?></span>
						<span><?= h(t('pattern')) ?> <?= h($currentGloveSeed) ?></span>
					</div>
					<div class="settings-row">
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#gloveTypeModal">
							<?= h(t('choose_type')) ?>
						</button>
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#gloveSkinModal" <?= $actualGloveDefindex === 0 ? 'disabled' : '' ?>>
							<?= h(t('choose_skin')) ?>
						</button>
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#gloveModal" <?= ($actualGloveDefindex === 0 || $currentGlovePaintId === 0) ? 'disabled' : '' ?>>
							<?= h(t('edit')) ?>
						</button>
					</div>

					<form method="post" class="modal-form">
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="modal fade skin-picker-modal" id="gloveTypeModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_type_title')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<div class="skin-picker-grid">
											<?php foreach ($gloveTypes as $gloveDefindex => $gloveType) : ?>
												<?php
												$gloveTypePlaceholder = glovePlaceholderImage((int)$gloveDefindex);
												$gloveTypeImage = (string)($gloveType['image_url'] ?? '');
												?>
												<button type="submit" name="forma" value="glove-<?= (int)$gloveDefindex ?>" class="skin-result <?= $actualGloveDefindex === (int)$gloveDefindex ? 'active' : '' ?>">
													<?php if ($gloveTypePlaceholder !== '') : ?>
														<img src="<?= h($gloveTypePlaceholder) ?>" alt="">
													<?php elseif ($gloveTypeImage !== '') : ?>
														<img src="<?= h($gloveTypeImage) ?>" alt="">
													<?php else : ?>
														<div class="empty-image"><?= h($gloveType['paint_name']) ?></div>
													<?php endif; ?>
													<span><?= h($gloveType['paint_name']) ?></span>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>

					<form method="post" class="modal-form">
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="modal fade skin-picker-modal" id="gloveSkinModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_skin_title')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<?php if ($actualGloveDefindex === 0) : ?>
											<p class="hint"><?= h(t('choose_glove_hint')) ?></p>
										<?php else : ?>
											<div class="skin-picker-grid">
												<?php foreach ($gloveSkinOptions as $paintKey => $paint) : ?>
													<?php $gloveSkinImage = (string)($paint['image_url'] ?? ''); ?>
													<button type="submit" name="skin_forma" value="gloveskin-<?= (int)$actualGloveDefindex ?>-<?= (int)$paintKey ?>" class="skin-result <?= $currentGlovePaintId === (int)$paintKey ? 'active' : '' ?>">
														<?php if ($glovePlaceholder !== '') : ?>
															<img src="<?= h($glovePlaceholder) ?>" data-picker-remote-src="<?= h($gloveSkinImage) ?>" alt="">
														<?php elseif ($gloveSkinImage !== '') : ?>
															<img src="<?= h($gloveSkinImage) ?>" alt="">
														<?php else : ?>
															<div class="empty-image"><?= h($paint['paint_name']) ?></div>
														<?php endif; ?>
														<span><?= h($paint['paint_name']) ?></span>
													</button>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</form>

					<form method="post">
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<input type="hidden" name="forma" value="gloveskin-<?= (int)$actualGloveDefindex ?>-<?= (int)$currentGlovePaintId ?>">
						<div class="modal fade" id="gloveModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h($currentGloveSkin['paint_name']) ?> <?= h(t('settings')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<?php if ($actualGloveDefindex === 0) : ?>
											<p class="hint"><?= h(t('choose_glove_hint')) ?></p>
										<?php else : ?>
											<div class="row g-3">
												<div class="col-sm-6">
													<label><?= h(t('wear_value')) ?>
														<input type="number" step="any" min="0" max="1" value="<?= h($currentGloveWear) ?>" class="form-control" name="wear">
													</label>
												</div>
												<div class="col-sm-6">
													<label><?= h(t('pattern')) ?>
														<input type="number" min="0" max="1000" value="<?= h($currentGloveSeed) ?>" class="form-control" name="seed">
													</label>
												</div>
											</div>
										<?php endif; ?>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
										<?php if ($actualGloveDefindex > 0 && $currentGlovePaintId > 0) : ?><button type="submit" class="btn btn-primary"><?= h(t('save')) ?></button><?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>

				<?php if (in_array($team, [2, 3], true)) : ?>
					<?php
					$agentOptions = array_values(array_filter($agents, static fn($agent) => (int)($agent['team'] ?? 0) === $team));
					$currentAgent = $agentOptions[0] ?? ['model' => '', 'agent_name' => t('default_agent'), 'image' => ''];
					foreach ($agentOptions as $agent) {
						if (($selectedAgent ?? '') === $agent['model']) {
							$currentAgent = $agent;
							break;
						}
					}
					?>
					<div class="skin-card featured">
						<div class="card-title-wrap">
							<span><?= h($team === 2 ? t('t_agent') : t('ct_agent')) ?></span>
							<h2><?= h($currentAgent['agent_name']) ?></h2>
						</div>
						<?php if (!empty($currentAgent['image'])) : ?>
							<img src="img/skins/agent.png" data-remote-src="<?= h($currentAgent['image']) ?>" class="skin-image" alt="">
						<?php else : ?>
							<img src="img/skins/agent.png" class="skin-image" alt="">
						<?php endif; ?>
						<div class="settings-row">
							<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#agentModal">
								<?= h(t('select')) ?>
							</button>
						</div>

						<form method="post" class="modal-form">
							<input type="hidden" name="action" value="save_agent">
							<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
							<input type="hidden" name="team" value="<?= $team ?>">
							<div class="modal fade agent-picker-modal" id="agentModal" tabindex="-1" aria-hidden="true">
								<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title"><?= h(t('choose_agent')) ?></h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
										</div>
										<div class="modal-body">
											<div class="agent-picker-grid">
												<?php foreach ($agentOptions as $agent) : ?>
													<?php $agentImage = (string)($agent['image'] ?? ''); ?>
													<button type="submit" name="agent_model" value="<?= h($agent['model']) ?>" class="agent-result <?= ($currentAgent['model'] ?? '') === $agent['model'] ? 'active' : '' ?>">
														<?php if ($agentImage !== '') : ?>
															<img src="img/skins/agent.png" data-remote-src="<?= h($agentImage) ?>" alt="">
														<?php else : ?>
															<img src="img/skins/agent.png" alt="">
														<?php endif; ?>
														<span><?= h($agent['agent_name']) ?></span>
													</button>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				<?php endif; ?>


				<?php if ($team === 1) : ?>
				<?php
				$currentMusicId = $selectedMusic !== null && array_key_exists((int)$selectedMusic, $music) ? (int)$selectedMusic : 0;
				$currentMusic = $music[$currentMusicId] ?? ($music[0] ?? ['id' => 0, 'name' => t('default_music'), 'image' => '']);
				$musicAliases = itemAliasNamesFromJson('music_en');
				?>
				<div class="skin-card featured">
					<div class="card-title-wrap">
						<span><?= h(t('music_kit')) ?></span>
						<h2><?= h($currentMusic['name']) ?></h2>
					</div>
					<?php if (!empty($currentMusic['image'])) : ?>
						<img src="img/skins/music_kit.png" data-remote-src="<?= h($currentMusic['image']) ?>" class="skin-image" alt="">
					<?php else : ?>
						<img src="img/skins/music_kit.png" class="skin-image" alt="">
					<?php endif; ?>
					<form method="post" class="modal-form">
						<input type="hidden" name="action" value="save_music">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="settings-row">
							<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#musicModal">
								<?= h(t('select')) ?>
							</button>
						</div>
						<div class="modal fade skin-picker-modal" id="musicModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_music')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<input type="search" class="form-control picker-search" placeholder="<?= h(t('search_music')) ?>" autocomplete="off" data-picker-search>
										<div class="skin-picker-grid">
											<?php foreach ($music as $musicId => $musicKit) : ?>
												<?php $musicImage = (string)($musicKit['image'] ?? ''); ?>
												<?php $musicSearchText = trim(($musicKit['name'] ?? '') . ' ' . ($musicAliases[(int)$musicId] ?? '')); ?>
												<button type="submit" name="music_id" value="<?= (int)$musicId ?>" class="skin-result <?= $currentMusicId === (int)$musicId ? 'active' : '' ?>" data-picker-result data-search="<?= h($musicSearchText) ?>">
													<?php if ($musicImage !== '') : ?>
														<img src="img/skins/music_kit.png" data-picker-remote-src="<?= h($musicImage) ?>" alt="">
													<?php else : ?>
														<img src="img/skins/music_kit.png" alt="">
													<?php endif; ?>
													<span><?= h($musicKit['name']) ?></span>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>

				<?php endif; ?>
				<?php
				$currentPinId = $selectedPin !== null && array_key_exists((int)$selectedPin, $pins) ? (int)$selectedPin : 0;
				$currentPin = $pins[$currentPinId] ?? ($pins[0] ?? ['id' => 0, 'name' => t('default_pin'), 'image' => '']);
				$pinAliases = itemAliasNamesFromJson('collectibles_en');
				?>
				<div class="skin-card featured">
					<div class="card-title-wrap">
						<span><?= h(t('pin')) ?></span>
						<h2><?= h($currentPin['name']) ?></h2>
					</div>
					<?php if (!empty($currentPin['image'])) : ?>
						<img src="img/skins/pin.png" data-remote-src="<?= h($currentPin['image']) ?>" class="skin-image" alt="">
					<?php else : ?>
						<img src="img/skins/pin.png" class="skin-image" alt="">
					<?php endif; ?>
					<form method="post" class="modal-form">
						<input type="hidden" name="action" value="save_pin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="settings-row">
							<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#pinModal">
								<?= h(t('select')) ?>
							</button>
						</div>
						<div class="modal fade skin-picker-modal" id="pinModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_pin')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<input type="search" class="form-control picker-search" placeholder="<?= h(t('search_pin')) ?>" autocomplete="off" data-picker-search>
										<div class="skin-picker-grid">
											<?php foreach ($pins as $pinId => $pin) : ?>
												<?php $pinImage = (string)($pin['image'] ?? ''); ?>
												<?php $pinSearchText = trim(($pin['name'] ?? '') . ' ' . ($pinAliases[(int)$pinId] ?? '')); ?>
												<button type="submit" name="pin_id" value="<?= (int)$pinId ?>" class="skin-result <?= $currentPinId === (int)$pinId ? 'active' : '' ?>" data-picker-result data-search="<?= h($pinSearchText) ?>">
													<?php if ($pinImage !== '') : ?>
														<img src="img/skins/pin.png" data-picker-remote-src="<?= h($pinImage) ?>" alt="">
													<?php else : ?>
														<img src="img/skins/pin.png" alt="">
													<?php endif; ?>
													<span><?= h($pin['name']) ?></span>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
				<?php foreach ($weapons as $defindex => $default) : ?>
					<?php
					if (in_array((int)$defindex, knifeDefindexes($knifes), true) || in_array((int)$defindex, gloveDefindexes($gloves), true)) {
						continue;
					}
					$hasSkin = array_key_exists($defindex, $selectedSkins);
					$currentPaintId = $hasSkin ? (int)$selectedSkins[$defindex]['weapon_paint_id'] : 0;
					$currentSkin = $hasSkin && isset($skins[$defindex][$currentPaintId]) ? $skins[$defindex][$currentPaintId] : $default;
					$initialWearValue = $hasSkin ? $selectedSkins[$defindex]['weapon_wear'] : 0.0;
					$initialSeedValue = $hasSkin ? $selectedSkins[$defindex]['weapon_seed'] : 0;
					$initialStatTrakValue = $hasSkin ? (int)($selectedSkins[$defindex]['weapon_stattrak'] ?? 0) : 0;
					$initialStatTrakCountValue = $initialStatTrakValue ? (int)($selectedSkins[$defindex]['weapon_stattrak_count'] ?? 0) : 0;
					$initialNameTagValue = $hasSkin ? ($selectedSkins[$defindex]['weapon_nametag'] ?? null) : null;
					$initialStickerValues = $hasSkin ? stickerValuesFromRow($selectedSkins[$defindex]) : defaultStickerValues();
					$stickerSlotTotal = stickerSlotCount((int)$defindex);
					if ($hasSkin) {
						$cachedSkinSetting = loadSkinSettingCache($db, $skinSettingsTable, $currentPreset['steamid'], $displayTeam, (int)$defindex, $currentPaintId);
						if ($cachedSkinSetting) {
							$initialWearValue = $cachedSkinSetting['weapon_wear'];
							$initialSeedValue = $cachedSkinSetting['weapon_seed'];
							$initialStatTrakValue = (int)$cachedSkinSetting['weapon_stattrak'];
							$initialStatTrakCountValue = $initialStatTrakValue ? (int)($cachedSkinSetting['weapon_stattrak_count'] ?? 0) : 0;
							$initialNameTagValue = $cachedSkinSetting['weapon_nametag'];
						}
					}
					$initialNameTagEnabled = $initialNameTagValue !== null && $initialNameTagValue !== '';
					$initialStickerIds = array_map('stickerIdFromValue', $initialStickerValues);
					$initialKeychainValue = (string)($selectedRowsByDefindex[(int)$defindex]['weapon_keychain'] ?? defaultKeychainValue());
					$initialKeychainParts = keychainValueParts($initialKeychainValue);
					$initialKeychain = $keychains[$initialKeychainParts['id']] ?? $keychains[0];
					$inspectRow = [];
					for ($inspectSlot = 0; $inspectSlot < 5; $inspectSlot++) {
						$inspectRow["weapon_sticker_{$inspectSlot}"] = $initialStickerValues[$inspectSlot] ?? defaultStickerValue();
					}
					$inspectRow['weapon_wear'] = $initialWearValue;
					$inspectRow['weapon_seed'] = $initialSeedValue;
					$inspectRow['weapon_stattrak'] = $initialStatTrakValue;
					// getSelectedSkins() ne remonte pas le compteur StatTrak : sans
					// valeur en cache, on le relit sur la ligne brute pour que le
					// lien porte le compteur réellement enregistré.
					$inspectStatTrakCount = (int)($selectedRowsByDefindex[(int)$defindex]['weapon_stattrak_count'] ?? 0);
					if ($initialStatTrakCountValue > 0) {
						$inspectStatTrakCount = $initialStatTrakCountValue;
					}
					$inspectRow['weapon_stattrak_count'] = $initialStatTrakValue ? $inspectStatTrakCount : 0;
					$inspectRow['weapon_nametag'] = $initialNameTagValue;
					$inspectRow['weapon_paint_id'] = $currentPaintId;
					$inspectHex = $currentPaintId > 0
						? InspectLink::encode(inspectItemFromRow($inspectRow, (int)$defindex, $initialKeychainValue))
						: '';
					$modalId = "weaponModal{$defindex}";
					$skinPickerId = "skinPicker{$defindex}";
					?>
					<div class="skin-card weapon-card">
						<?php if ($initialNameTagEnabled || $initialStatTrakValue) : ?>
							<div class="card-status-badges">
								<?php if ($initialNameTagEnabled) : ?><span class="nametag-badge"><?= h(t('name_tag')) ?></span><?php endif; ?>
								<?php if ($initialStatTrakValue) : ?><span class="stattrak-badge">StatTrak™</span><?php endif; ?>
							</div>
						<?php endif; ?>
						<div class="card-title-wrap">
							<span><?= h($default['weapon_name']) ?></span>
							<h2><?= h($currentSkin['paint_name']) ?></h2>
						</div>
						<?php $weaponPlaceholder = weaponPlaceholderImage($default['weapon_name'] ?? ''); ?>
						<?php if ($weaponPlaceholder !== '') : ?>
							<img src="<?= h($weaponPlaceholder) ?>" data-remote-src="<?= h($currentSkin['image_url'] ?? '') ?>" class="skin-image" alt="">
						<?php else : ?>
							<img src="<?= h($currentSkin['image_url']) ?>" class="skin-image" alt="">
						<?php endif; ?>
						<form method="post">
							<input type="hidden" name="action" value="save_skin">
							<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
							<input type="hidden" name="team" value="<?= $team ?>">
							<input type="hidden" name="forma" value="<?= (int)$defindex ?>-<?= (int)$currentPaintId ?>">
							<?php $cardStickerIds = array_values(array_filter(array_slice($initialStickerIds, 0, $stickerSlotTotal), static fn($stickerId) => (int)$stickerId > 0)); ?>
							<?php if ($cardStickerIds) : ?>
								<div class="card-stickers" aria-label="<?= h(t('stickers')) ?>">
									<?php foreach ($cardStickerIds as $cardStickerId) : ?>
										<?php $cardSticker = $stickers[(int)$cardStickerId] ?? null; ?>
										<?php if ($cardSticker) : ?>
											<img src="img/skins/sticker.png" data-remote-src="<?= h($cardSticker['image'] ?? '') ?>" alt="<?= h($cardSticker['name'] ?? '') ?>" title="<?= h($cardSticker['name'] ?? '') ?>">
										<?php endif; ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<div class="skin-meta">
								<span><?= h(t('wear_value')) ?> <?= h($initialWearValue) ?></span>
								<span><?= h(t('pattern')) ?> <?= h($initialSeedValue) ?></span>
							</div>
							<div class="settings-row">
								<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#<?= h($skinPickerId) ?>">
									<?= h(t('choose_skin')) ?>
								</button>
								<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#<?= h($modalId) ?>" <?= $currentPaintId === 0 ? 'disabled' : '' ?>>
									<?= h(t('edit')) ?>
								</button>
							</div>

							<div class="modal fade skin-picker-modal" id="<?= h($skinPickerId) ?>" tabindex="-1" aria-hidden="true">
								<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title"><?= h(t('choose_skin_title')) ?></h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
										</div>
										<div class="modal-body">
											<div class="skin-picker-grid">
												<?php foreach ($skins[$defindex] as $paintKey => $paint) : ?>
													<?php $paintImage = (string)($paint['image_url'] ?? ''); ?>
													<button type="submit" name="skin_forma" value="<?= (int)$defindex ?>-<?= (int)$paintKey ?>" class="skin-result <?= $currentPaintId === (int)$paintKey ? 'active' : '' ?>">
														<?php if ($weaponPlaceholder !== '') : ?>
															<img src="<?= h($weaponPlaceholder) ?>" data-picker-remote-src="<?= h($paintImage) ?>" alt="">
														<?php elseif ($paintImage !== '') : ?>
															<img src="<?= h($paintImage) ?>" alt="">
														<?php else : ?>
															<div class="empty-image"><?= h($paint['paint_name']) ?></div>
														<?php endif; ?>
														<span><?= h($paint['paint_name']) ?></span>
													</button>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="modal fade" id="<?= h($modalId) ?>" tabindex="-1" aria-hidden="true">
								<div class="modal-dialog modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title"><?= h($currentSkin['paint_name']) ?> <?= h(t('settings')) ?></h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
										</div>
										<div class="modal-body">
											<div class="row g-3">
												<div class="col-sm-6">
													<label><?= h(t('wear_value')) ?>
														<input type="number" step="any" min="0" max="1" value="<?= h($initialWearValue) ?>" class="form-control" id="wear<?= (int)$defindex ?>" name="wear">
													</label>
												</div>
												<div class="col-sm-6">
													<label><?= h(t('pattern')) ?>
														<input type="number" min="0" max="1000" value="<?= h($initialSeedValue) ?>" class="form-control" name="seed">
													</label>
												</div>
												<div class="col-12 nametag-row">
													<input type="hidden" name="nametag_present" value="1">
													<label class="check-line">
														<input type="checkbox" name="nametag_enabled" value="1" data-nametag-toggle <?= $initialNameTagEnabled ? 'checked' : '' ?>>
														<span class="nametag-label"><?= h(t('name_tag')) ?></span>
													</label>
													<input type="text" name="weapon_nametag" value="<?= h($initialNameTagValue ?? '') ?>" maxlength="20" class="form-control nametag-input" data-nametag-input <?= $initialNameTagEnabled ? '' : 'disabled hidden' ?>>
												</div>
												<div class="col-12 stattrak-row">
	<label class="check-line">
		<input type="checkbox" name="stattrak" value="1" data-stattrak-toggle <?= $initialStatTrakValue ? 'checked' : '' ?>>
		<span class="stattrak-label">StatTrak™</span>
	</label>
	<input type="number" name="weapon_stattrak_count" value="<?= h($initialStatTrakCountValue) ?>" min="0" max="999999" step="1" class="form-control stattrak-input" data-stattrak-input <?= $initialStatTrakValue ? '' : 'disabled hidden' ?>>
</div>
												<div class="col-12 sticker-section">
													<input type="hidden" name="sticker_present" value="1">
													<div class="sticker-section-heading">
														<div class="sticker-section-title"><?= h(t('stickers')) ?></div>
														<div class="sticker-tool-buttons">
															<span class="sticker-tool-button-wrap" title="<?= h(t('apply_sticker_to_all')) ?>">
																<button type="button" class="btn btn-sm btn-outline-light sticker-tool-button" data-sticker-fill-all title="<?= h(t('apply_sticker_to_all')) ?>" aria-label="<?= h(t('apply_sticker_to_all')) ?>" disabled>
																	↻
																</button>
															</span>
															<span class="sticker-tool-button-wrap" title="<?= h(t('clear_all_stickers')) ?>">
																<button type="button" class="btn btn-sm btn-outline-light sticker-tool-button" data-sticker-clear-all title="<?= h(t('clear_all_stickers')) ?>" aria-label="<?= h(t('clear_all_stickers')) ?>" disabled>
																	×
																</button>
															</span>
														</div>
													</div>
													<div class="sticker-grid">
														<?php for ($slotIndex = 0; $slotIndex < $stickerSlotTotal; $slotIndex++) : ?>
													<?php
													$currentStickerValue = $initialStickerValues[$slotIndex] ?? defaultStickerValue();
													$currentStickerId = $initialStickerIds[$slotIndex] ?? 0;
													$currentSticker = $stickers[$currentStickerId] ?? $stickers[0];
													?>
													<div class="sticker-slot" data-empty-label="<?= h(t('sticker_slot') . ' ' . ($slotIndex + 1)) ?>" data-slot-number="<?= $slotIndex + 1 ?>" data-sticker-slot-index="<?= $slotIndex ?>" data-weapon-defindex="<?= (int)$defindex ?>" data-saved-sticker-id="<?= (int)$currentStickerId ?>">
														<input type="hidden" name="sticker_<?= $slotIndex ?>" value="<?= (int)$currentStickerId ?>" data-sticker-input>
														<input type="hidden" name="sticker_value_<?= $slotIndex ?>" value="<?= h($currentStickerValue) ?>" data-sticker-value>
														<div class="sticker-slot-preview">
															<button type="button" class="sticker-slot-button" data-sticker-open aria-label="<?= h(t('choose_sticker')) ?>">
																<span class="sticker-plus sticker-empty-icon" <?= $currentStickerId > 0 ? 'hidden' : '' ?>>+</span>
																<img src="img/skins/sticker.png" data-remote-src="<?= h($currentSticker['image'] ?? '') ?>" alt="" data-sticker-preview <?= $currentStickerId > 0 ? '' : 'hidden' ?> >
															</button>
															<button type="button" class="sticker-slot-settings" data-sticker-settings title="<?= h(t('sticker_settings')) ?>" aria-label="<?= h(t('sticker_settings')) ?>" <?= $currentStickerId > 0 ? '' : 'hidden disabled' ?>>⚙</button>
														</div>
														<div class="sticker-slot-name" data-sticker-name><span data-sticker-name-text><?= h($currentStickerId > 0 ? ($currentSticker['name'] ?? '') : t('sticker_slot') . ' ' . ($slotIndex + 1)) ?></span></div>
													</div>
														<?php endfor; ?>
													</div>
												</div>
												<?php if ($hasKeychainColumn) : ?>
													<div class="col-12 keychain-section">
														<input type="hidden" name="keychain_present" value="1">
														<input type="hidden" name="keychain_value" value="<?= h($initialKeychainValue) ?>">
														<div class="row g-3">
															<div class="col-sm-8">
																<label><?= h(t('keychain')) ?>
																	<select name="keychain_id" class="form-control">
																		<?php foreach ($keychains as $keychainId => $keychainItem) : ?>
																			<option value="<?= (int)$keychainId ?>" <?= (int)$keychainId === (int)$initialKeychainParts['id'] ? 'selected' : '' ?>><?= h($keychainItem['name']) ?></option>
																		<?php endforeach; ?>
																	</select>
																</label>
															</div>
															<div class="col-sm-4">
																<label><?= h(t('keychain_seed')) ?>
																	<input type="number" name="keychain_seed" min="0" max="100000" step="1" value="<?= (int)$initialKeychainParts['seed'] ?>" class="form-control">
																</label>
															</div>
														</div>
													</div>
												<?php endif; ?>
												<div class="col-12 inspect-section">
													<div class="inspect-section-title"><?= h(t('inspect_preview')) ?></div>
													<p class="inspect-hint"><?= h(t('inspect_3d_hint')) ?></p>
													<div class="inspect-actions">
														<?php if ($inspectHex !== '') : ?>
															<a class="btn btn-sm btn-outline-light" href="<?= h(InspectLink::viewerUrl($inspectHex)) ?>" target="_blank" rel="noopener noreferrer"><?= h(t('inspect_3d_edit')) ?></a>
														<?php else : ?>
															<button type="button" class="btn btn-sm btn-outline-light" disabled><?= h(t('inspect_3d_edit')) ?></button>
														<?php endif; ?>
														<button type="button" class="btn btn-sm btn-outline-light" data-inspect-import data-inspect-weapon-defindex="<?= (int)$defindex ?>" data-inspect-weapon-name="<?= h($default['weapon_name'] . ' - ' . $currentSkin['paint_name']) ?>"><?= h(t('inspect_import')) ?></button>
													</div>
												</div>
											</div>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
											<button type="submit" class="btn btn-primary"><?= h(t('save')) ?></button>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<div class="modal fade inspect-import-modal" id="inspectImportModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<form method="post" class="modal-content">
				<input type="hidden" name="action" value="import_inspect_link">
				<input type="hidden" name="id" value="<?= h($currentPreset['steamid'] ?? '') ?>">
				<input type="hidden" name="team" value="<?= h((string)($team ?? 1)) ?>">
				<input type="hidden" name="weapon_defindex" value="" data-inspect-defindex>
				<div class="modal-header">
					<h5 class="modal-title"><?= h(t('inspect_import')) ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body">
					<div class="inspect-import-weapon" data-inspect-weapon-label></div>
					<p class="inspect-hint"><?= h(t('inspect_3d_hint')) ?></p>
					<input type="text" name="inspect_link" class="form-control" placeholder="<?= h(t('inspect_import_placeholder')) ?>" autocomplete="off" spellcheck="false" required data-inspect-input>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
					<button type="submit" class="btn btn-primary"><?= h(t('inspect_import_apply')) ?></button>
				</div>
			</form>
		</div>
	</div>
	<div class="modal fade sticker-picker-modal" id="stickerPickerModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?= h(t('choose_sticker')) ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body">
					<input type="search" class="form-control sticker-search" placeholder="<?= h(t('search_sticker')) ?>" autocomplete="off">
					<div class="sticker-picker-grid" data-sticker-results></div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade sticker-advanced-modal" id="stickerAdvancedModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<form method="post" class="modal-content" data-sticker-advanced-form>
				<input type="hidden" name="action" value="save_sticker_slot">
				<input type="hidden" name="id" value="<?= h($currentPreset['steamid'] ?? '') ?>" data-sticker-advanced-id>
				<input type="hidden" name="team" value="<?= h((string)($team ?? 1)) ?>" data-sticker-advanced-team>
				<input type="hidden" name="weapon_defindex" value="" data-sticker-advanced-defindex>
				<input type="hidden" name="sticker_slot" value="" data-sticker-advanced-slot>
				<div class="modal-header">
					<div>
						<h5 class="modal-title" data-sticker-advanced-title><?= h(t('sticker_slot_settings')) ?></h5>
						<div class="sticker-advanced-subtitle" data-sticker-advanced-name></div>
					</div>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body sticker-advanced-body">
					<?php $stickerParams = [
						'wear' => [t('sticker_wear'), '0', '1', '0.01', '0.00'],
						'x' => [t('sticker_x'), '-1', '1', '0.01', '0.00'],
						'y' => [t('sticker_y'), '-1', '1', '0.01', '0.00'],
						'scale' => [t('sticker_scale'), '0.2', '5', '0.01', '1.00'],
						'rotation' => [t('sticker_rotation'), '0', '360', '1', '0'],
					]; ?>
					<?php foreach ($stickerParams as $paramKey => $paramConfig) : ?>
						<div class="sticker-advanced-row" data-sticker-param="<?= h($paramKey) ?>">
							<label><?= h($paramConfig[0]) ?></label>
							<div class="sticker-advanced-controls">
								<input type="range" min="<?= h($paramConfig[1]) ?>" max="<?= h($paramConfig[2]) ?>" step="<?= h($paramConfig[3]) ?>" value="<?= h($paramConfig[4]) ?>" data-sticker-param-range>
								<input type="number" name="sticker_<?= h($paramKey) ?>" min="<?= h($paramConfig[1]) ?>" max="<?= h($paramConfig[2]) ?>" step="<?= h($paramConfig[3]) ?>" value="<?= h($paramConfig[4]) ?>" class="form-control" data-sticker-param-number>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-light" data-sticker-advanced-reset><?= h(t('reset')) ?></button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
					<button type="submit" class="btn btn-primary"><?= h(t('save')) ?></button>
				</div>
			</form>
		</div>
	</div>

	<?php if ($accessGranted) : ?>
		<div class="modal fade" id="loadoutPasswordModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-sm">
				<form method="post" class="modal-content">
					<input type="hidden" name="action" value="verify_loadout_password">
					<input type="hidden" name="id" value="" data-loadout-password-id-input>
					<input type="hidden" name="team" value="1" data-loadout-password-team-input>
					<div class="modal-header">
						<div>
							<h5 class="modal-title"><?= h(t('enter_loadout_password')) ?></h5>
							<div class="modal-subtitle" data-loadout-password-label></div>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('cancel')) ?>"></button>
					</div>
					<div class="modal-body form-grid">
						<p class="hint"><?= h(t('loadout_password_prompt')) ?></p>
						<div class="alert alert-danger d-none" data-loadout-password-error><?= h(t('loadout_password_incorrect')) ?></div>
						<label><?= h(t('enter_loadout_password')) ?>
							<input class="form-control" type="password" name="loadout_password" autocomplete="one-time-code" required data-loadout-password-modal-input>
						</label>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
						<button type="submit" class="btn btn-primary"><?= h(t('edit')) ?></button>
					</div>
				</form>
			</div>
		</div>

		<div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-sm">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title"><?= h(isAdmin() ? t('admin_enabled') : t('admin_login')) ?></h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('cancel')) ?>"></button>
					</div>
					<div class="modal-body">
						<?php if (adminPassword() === '') : ?>
							<div class="alert alert-info mb-0"><?= h(t('admin_disabled')) ?></div>
						<?php elseif (isAdmin()) : ?>
							<p class="hint"><?= h(t('admin_enabled')) ?></p>
						<?php else : ?>
							<?php if ($adminError) : ?><div class="alert alert-danger"><?= h(t('admin_invalid')) ?></div><?php endif; ?>
							<form method="post" class="form-grid" id="adminLoginForm">
								<input type="hidden" name="action" value="admin_login">
								<input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
								<label><?= h(t('admin_password')) ?>
									<input class="form-control" type="password" name="admin_password" autocomplete="current-password" required>
								</label>
							</form>
						<?php endif; ?>
					</div>
					<?php if (adminPassword() !== '') : ?>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
							<?php if (isAdmin()) : ?>
								<form method="post">
									<input type="hidden" name="action" value="admin_logout">
									<input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
									<button class="btn btn-outline-danger" type="submit"><?= h(t('admin_exit')) ?></button>
								</form>
							<?php else : ?>
								<button class="btn btn-primary" type="submit" form="adminLoginForm"><?= h(t('admin_enter')) ?></button>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

		<?php if ($accessGranted) : ?>
			<button class="admin-button<?= isAdmin() ? ' active' : '' ?>" type="button" data-bs-toggle="modal" data-bs-target="#adminModal" aria-label="<?= h(isAdmin() ? t('admin_enabled') : t('admin')) ?>" title="<?= h(isAdmin() ? t('admin_enabled') : t('admin')) ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 6v5.2c0 4.6 3.1 8.2 7.5 9.8 4.4-1.6 7.5-5.2 7.5-9.8V6L12 3Z"></path><path d="M9.5 12.2 11.2 14l3.6-4"></path></svg>
			</button>
		<?php endif; ?>
		<nav class="language-switch" aria-label="<?= h(t('language')) ?>">
			<details class="language-menu">
				<summary class="language-button" aria-label="<?= h(t('language')) ?>" title="<?= h(t('language')) ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<circle cx="12" cy="12" r="9"></circle>
						<path d="M3 12h18M12 3c2.4 2.7 3.6 5.7 3.6 9s-1.2 6.3-3.6 9M12 3c-2.4 2.7-3.6 5.7-3.6 9s1.2 6.3 3.6 9"></path>
					</svg>
				</summary>
				<div class="language-dropdown">
					<?php foreach ($availableLanguages as $languageCode => $languageName) : ?>
						<a class="<?= $currentLanguage === $languageCode ? 'active' : '' ?>" href="<?= h(languageUrl($languageCode)) ?>"><?= h($languageName) ?></a>
					<?php endforeach; ?>
				</div>
			</details>
		</nav>
		<footer class="site-footer">Copyright © 2026 wtf729 - All rights reserved</footer>
	</main>
	<script>
		window.cs2StickerDataUrl = <?= json_encode(dataFileUrl(stickerDataFile()), JSON_UNESCAPED_SLASHES) ?>;
		window.cs2StickerAliasDataUrl = <?= json_encode(stickerAliasDataFile() !== '' ? dataFileUrl(stickerAliasDataFile()) : '', JSON_UNESCAPED_SLASHES) ?>;
		(function () {
			document.querySelectorAll('[data-loadout-password-toggle]').forEach(function (toggle) {
				var form = toggle.closest('form');
				var wrap = form ? form.querySelector('[data-loadout-password-input-wrap]') : null;
				var input = form ? form.querySelector('[data-loadout-password-input]') : null;
				var panel = form ? form.closest('.loadout-info-panel') : null;
				var status = panel ? panel.querySelector('[data-loadout-password-status]') : null;
				var sync = function () {
					if (wrap) wrap.classList.toggle('d-none', !toggle.checked);
					if (input) {
						input.disabled = !toggle.checked;
						input.required = toggle.checked && input.hasAttribute('data-loadout-password-required-when-enabled');
					}
					if (status) {
						status.classList.toggle('active', toggle.checked);
						status.textContent = toggle.checked ? status.dataset.enabledLabel : status.dataset.disabledLabel;
					}
				};
				toggle.addEventListener('change', sync);
				sync();
			});

			var loadoutPasswordModalEl = document.getElementById('loadoutPasswordModal');
			if (loadoutPasswordModalEl) {
				loadoutPasswordModalEl.addEventListener('show.bs.modal', function (event) {
					var trigger = event.relatedTarget;
					if (!trigger) return;
					loadoutPasswordModalEl.querySelector('[data-loadout-password-id-input]').value = trigger.dataset.loadoutPasswordId || '';
					loadoutPasswordModalEl.querySelector('[data-loadout-password-team-input]').value = trigger.dataset.loadoutPasswordTeam || '1';
					loadoutPasswordModalEl.querySelector('[data-loadout-password-label]').textContent = trigger.dataset.loadoutPasswordLabel || '';
					loadoutPasswordModalEl.querySelector('[data-loadout-password-error]').classList.toggle('d-none', trigger.dataset.loadoutPasswordError !== '1');
				});
				loadoutPasswordModalEl.addEventListener('shown.bs.modal', function () {
					var input = loadoutPasswordModalEl.querySelector('[data-loadout-password-modal-input]');
					if (input) input.focus();
				});
				loadoutPasswordModalEl.addEventListener('hidden.bs.modal', function () {
					var input = loadoutPasswordModalEl.querySelector('[data-loadout-password-modal-input]');
					if (input) input.value = '';
				});
				var requestedLoadoutPasswordId = <?= json_encode((string)($_GET['loadout_password_error'] ?? $_GET['loadout_password_required'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
				if (requestedLoadoutPasswordId) {
					var trigger = document.querySelector('[data-loadout-password-id="' + CSS.escape(requestedLoadoutPasswordId) + '"]');
					if (trigger) {
						trigger.dataset.loadoutPasswordTeam = <?= json_encode((string)($_GET['loadout_password_team'] ?? '1')) ?>;
						if (<?= isset($_GET['loadout_password_error']) ? 'true' : 'false' ?>) trigger.dataset.loadoutPasswordError = '1';
						bootstrap.Modal.getOrCreateInstance(loadoutPasswordModalEl).show(trigger);
					}
				}
			}

			<?php if ($adminError && $accessGranted) : ?>
			var adminModalEl = document.getElementById('adminModal');
			if (adminModalEl) bootstrap.Modal.getOrCreateInstance(adminModalEl).show();
			<?php endif; ?>

			var loadRemoteImage = function (image) {
				if (!image) return;
				var remoteSrc = image.dataset.remoteSrc || '';
				if (!remoteSrc || remoteSrc === image.src) return;
				var probe = new Image();
				probe.onload = function () {
					image.src = remoteSrc;
				};
				probe.src = remoteSrc;
			};

			document.querySelectorAll('img[data-remote-src]').forEach(loadRemoteImage);
			var filterPickerResults = function (modal) {
				if (!modal) return;
				var input = modal.querySelector('[data-picker-search]');
				var query = input ? input.value.trim().toLowerCase() : '';
				var terms = query ? query.split(/\s+/).filter(Boolean) : [];
				modal.querySelectorAll('[data-picker-result]').forEach(function (result) {
					var searchText = (result.dataset.search || result.textContent || '').toLowerCase();
					var id = result.value || result.dataset.id || '';
					result.hidden = !!query && String(id) !== query && !terms.every(function (term) {
						return searchText.indexOf(term) !== -1;
					});
				});
			};
			document.querySelectorAll('.skin-picker-modal').forEach(function (modal) {
				var search = modal.querySelector('[data-picker-search]');
				if (search) {
					search.addEventListener('input', function () {
						filterPickerResults(modal);
					});
				}
				modal.addEventListener('show.bs.modal', function () {
					if (search) search.value = '';
					filterPickerResults(modal);
					modal.querySelectorAll('img[data-picker-remote-src]').forEach(function (image) {
						image.dataset.remoteSrc = image.dataset.pickerRemoteSrc || '';
						image.removeAttribute('data-picker-remote-src');
						loadRemoteImage(image);
					});
				});
				modal.addEventListener('shown.bs.modal', function () {
					if (search) search.focus();
				});
			});

			var params = new URLSearchParams(location.search);
			var key = 'cs2_wp_scroll_' + location.pathname + '_' + (params.get('action') || '') + '_' + (params.get('id') || '') + '_' + (params.get('team') || '');
			window.rememberScrollPosition = function () {
				sessionStorage.setItem(key, String(window.scrollY || window.pageYOffset || 0));
			};
			var savedY = sessionStorage.getItem(key);
			if (savedY !== null) {
				sessionStorage.removeItem(key);
				if ('scrollRestoration' in history) {
					history.scrollRestoration = 'manual';
				}
				var html = document.documentElement;
				var previousScrollBehavior = html.style.scrollBehavior;
				html.style.scrollBehavior = 'auto';
				window.scrollTo(0, parseInt(savedY, 10) || 0);
				requestAnimationFrame(function () {
					html.style.scrollBehavior = previousScrollBehavior;
				});
			}

			document.addEventListener('submit', function () {
				window.rememberScrollPosition();
			}, true);


			var stickerData = null;
			var activeStickerSlot = null;
			var activeStickerUnderlay = null;
			var pickerEl = document.getElementById('stickerPickerModal');
			var picker = pickerEl && window.bootstrap ? new bootstrap.Modal(pickerEl) : null;
			var searchInput = pickerEl ? pickerEl.querySelector('.sticker-search') : null;
			var resultsEl = pickerEl ? pickerEl.querySelector('[data-sticker-results]') : null;
			var activeAdvancedSlot = null;
			var advancedEl = document.getElementById('stickerAdvancedModal');
			var advancedModal = advancedEl && window.bootstrap ? new bootstrap.Modal(advancedEl) : null;
			var advancedForm = advancedEl ? advancedEl.querySelector('[data-sticker-advanced-form]') : null;
			var advancedTitle = advancedEl ? advancedEl.querySelector('[data-sticker-advanced-title]') : null;
			var advancedName = advancedEl ? advancedEl.querySelector('[data-sticker-advanced-name]') : null;
			var advancedTitleTemplate = <?= json_encode(t('sticker_slot_settings'), JSON_UNESCAPED_UNICODE) ?>;
			var stickerSaveFailedMessage = <?= json_encode(t('sticker_save_failed'), JSON_UNESCAPED_UNICODE) ?>;
			var stickerDefaults = { wear: 0, x: 0, y: 0, scale: 1, rotation: 0 };
			var stickerParamConfig = {
				wear: { min: 0, max: 1, decimals: 2, defaultValue: 0 },
				x: { min: -1, max: 1, decimals: 2, defaultValue: 0 },
				y: { min: -1, max: 1, decimals: 2, defaultValue: 0 },
				scale: { min: 0.2, max: 5, decimals: 2, defaultValue: 1 },
				rotation: { min: 0, max: 360, decimals: 0, defaultValue: 0 }
			};

			var clampStickerParam = function (key, value, fallback) {
				var config = stickerParamConfig[key];
				var numeric = parseFloat(value);
				if (!config || !isFinite(numeric)) {
					return fallback !== undefined ? fallback : (config ? config.defaultValue : 0);
				}
				if (key === 'scale' && numeric <= 0) numeric = config.defaultValue;
				return Math.min(config.max, Math.max(config.min, numeric));
			};

			var formatStickerParam = function (key, value) {
				var config = stickerParamConfig[key];
				var normalized = clampStickerParam(key, value, config ? config.defaultValue : 0);
				return config && config.decimals > 0 ? normalized.toFixed(config.decimals) : String(Math.round(normalized));
			};

			var parseStickerValue = function (value) {
				var parts = String(value || '').split(';');
				while (parts.length < 7) parts.push('');
				var id = parseInt(parts[0], 10) || 0;
				var schema = parseInt(parts[1], 10) || 0;
				if (id > 0 && schema === 0) schema = id;
				return {
					id: id,
					schema: schema,
					x: clampStickerParam('x', parts[2], 0),
					y: clampStickerParam('y', parts[3], 0),
					wear: clampStickerParam('wear', parts[4], 0),
					scale: clampStickerParam('scale', parts[5], 1),
					rotation: clampStickerParam('rotation', parts[6], 0)
				};
			};

			var buildStickerValueForClient = function (id, schema, params) {
				id = parseInt(id, 10) || 0;
				schema = parseInt(schema, 10) || 0;
				if (!id) return '0;0;0;0;0;0;0';
				if (!schema) schema = id;
				params = params || stickerDefaults;
				return [
					id,
					schema,
					formatStickerParam('x', params.x),
					formatStickerParam('y', params.y),
					formatStickerParam('wear', params.wear),
					formatStickerParam('scale', params.scale),
					formatStickerParam('rotation', params.rotation)
				].join(';');
			};

			var defaultStickerValueForClient = function (id) {
				return buildStickerValueForClient(id, id, stickerDefaults);
			};

			var syncStickerSettingsButton = function (slot) {
				if (!slot) return;
				var input = slot.querySelector('[data-sticker-input]');
				var button = slot.querySelector('[data-sticker-settings]');
				var id = input ? String(input.value || '0') : '0';
				var savedId = String(slot.dataset.savedStickerId || '0');
				var enabled = id !== '0' && id === savedId;
				if (button) {
					button.hidden = !enabled;
					button.disabled = !enabled;
				}
			};

			var setAdvancedControls = function (params) {
				if (!advancedEl) return;
				Object.keys(stickerParamConfig).forEach(function (key) {
					var row = advancedEl.querySelector('[data-sticker-param="' + key + '"]');
					if (!row) return;
					var value = formatStickerParam(key, params[key]);
					var range = row.querySelector('[data-sticker-param-range]');
					var number = row.querySelector('[data-sticker-param-number]');
					if (range) range.value = value;
					if (number) number.value = value;
				});
			};

			var readAdvancedControls = function () {
				var params = {};
				if (!advancedEl) return Object.assign({}, stickerDefaults);
				Object.keys(stickerParamConfig).forEach(function (key) {
					var row = advancedEl.querySelector('[data-sticker-param="' + key + '"]');
					var number = row ? row.querySelector('[data-sticker-param-number]') : null;
					params[key] = clampStickerParam(key, number ? number.value : stickerDefaults[key], stickerDefaults[key]);
				});
				return params;
			};

			var normalizeAdvancedRow = function (row, source) {
				if (!row) return;
				var key = row.dataset.stickerParam;
				var range = row.querySelector('[data-sticker-param-range]');
				var number = row.querySelector('[data-sticker-param-number]');
				var previous = number && number.dataset.validValue !== undefined ? number.dataset.validValue : stickerDefaults[key];
				var raw = source && source.value !== '' ? source.value : previous;
				var value = formatStickerParam(key, raw);
				if (range) range.value = value;
				if (number) {
					number.value = value;
					number.dataset.validValue = value;
				}
			};

			var openStickerAdvanced = function (slot) {
				if (!slot || !advancedEl || !advancedModal) return;
				var info = stickerInfoFromSlot(slot);
				if (info.id === '0') return;
				activeAdvancedSlot = slot;
				var valueInput = slot.querySelector('[data-sticker-value]');
				var parts = parseStickerValue(valueInput ? valueInput.value : defaultStickerValueForClient(info.id));
				var titleSlot = slot.dataset.slotNumber || String((parseInt(slot.dataset.stickerSlotIndex, 10) || 0) + 1);
				if (advancedTitle) advancedTitle.textContent = advancedTitleTemplate.replace('{slot}', titleSlot);
				if (advancedName) advancedName.textContent = info.name || '';
				var defindexInput = advancedEl.querySelector('[data-sticker-advanced-defindex]');
				var slotInput = advancedEl.querySelector('[data-sticker-advanced-slot]');
				if (defindexInput) defindexInput.value = slot.dataset.weaponDefindex || '';
				if (slotInput) slotInput.value = slot.dataset.stickerSlotIndex || '0';
				setAdvancedControls(parts);
				setStickerUnderlay(slot.closest('.modal'));
				advancedModal.show();
				setTimeout(markStickerBackdrop, 0);
			};

			var saveStickerChoice = function (slot, id) {
				if (!window.fetch || !slot) return Promise.resolve(null);
				var form = slot.closest('form');
				var formData = new FormData();
				var idInput = form ? form.querySelector('input[name="id"]') : null;
				var teamInput = form ? form.querySelector('input[name="team"]') : null;
				formData.append('action', 'save_sticker_choice');
				formData.append('id', idInput ? idInput.value : '');
				formData.append('team', teamInput ? teamInput.value : '1');
				formData.append('weapon_defindex', slot.dataset.weaponDefindex || '0');
				formData.append('sticker_slot', slot.dataset.stickerSlotIndex || '0');
				formData.append('sticker_id', String(id || '0'));
				formData.append('ajax', '1');
				return fetch(window.location.href, {
					method: 'POST',
					body: formData,
					headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
				}).then(function (response) {
					return response.ok ? response.json() : Promise.reject();
				}).then(function (payload) {
					if (!payload || !payload.ok) throw new Error(payload && payload.message ? payload.message : stickerSaveFailedMessage);
					return payload;
				});
			};
			var setStickerUnderlay = function (modal) {
				if (activeStickerUnderlay && activeStickerUnderlay !== modal) {
					activeStickerUnderlay.classList.remove('sticker-underlay-active');
				}
				activeStickerUnderlay = modal || null;
				if (activeStickerUnderlay) {
					activeStickerUnderlay.classList.add('sticker-underlay-active');
				}
			};

			var markStickerBackdrop = function () {
				var backdrops = document.querySelectorAll('.modal-backdrop');
				var backdrop = backdrops.length ? backdrops[backdrops.length - 1] : null;
				if (backdrop) {
					backdrop.classList.add('sticker-picker-backdrop');
				}
			};

			if (pickerEl) {
				pickerEl.addEventListener('shown.bs.modal', markStickerBackdrop);
				pickerEl.addEventListener('hidden.bs.modal', function () {
					setStickerUnderlay(null);
				});
			}

			document.addEventListener('keydown', function (event) {
				if (event.key !== 'Escape') return;
				if (advancedEl && advancedEl.classList.contains('show')) {
					event.preventDefault();
					event.stopPropagation();
					if (advancedModal) advancedModal.hide();
					return;
				}
				if (pickerEl && pickerEl.classList.contains('show')) {
					event.preventDefault();
					event.stopPropagation();
					if (picker) picker.hide();
				}
			}, true);
			var fetchJson = function (url) {
				if (!url) return Promise.resolve([]);
				return fetch(url, { cache: 'no-cache' })
					.then(function (response) { return response.ok ? response.json() : []; });
			};

			var loadStickers = function () {
				if (stickerData) return Promise.resolve(stickerData);
				return Promise.all([
					fetchJson(window.cs2StickerDataUrl),
					fetchJson(window.cs2StickerAliasDataUrl)
				]).then(function (payloads) {
						var items = payloads[0] || [];
						var aliases = payloads[1] || [];
						var aliasById = {};
						var seen = {};
						aliases.forEach(function (item) {
							aliasById[parseInt(item.id, 10) || 0] = item.name || '';
						});
						stickerData = [{ id: 0, name: <?= json_encode(t('no_sticker'), JSON_UNESCAPED_UNICODE) ?>, image: '' }].concat(items.map(function (item) {
							var id = parseInt(item.id, 10) || 0;
							var name = item.name || '';
							var alias = aliasById[id] || '';
							seen[id] = true;
							return { id: id, name: name, image: item.image || '', searchText: name + ' ' + alias };
						}));
						aliases.forEach(function (item) {
							var id = parseInt(item.id, 10) || 0;
							if (!id || seen[id]) return;
							stickerData.push({ id: id, name: item.name || '', image: item.image || '', searchText: item.name || '' });
						});
						return stickerData;
					});
			};

			var renderStickerResults = function () {
				if (!resultsEl || !stickerData) return;
				var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
				var terms = query ? query.split(/\s+/).filter(Boolean) : [];
				var shown = stickerData.filter(function (item) {
					var searchText = (item.searchText || item.name || '').toLowerCase();
					return !query || String(item.id) === query || terms.every(function (term) {
						return searchText.indexOf(term) !== -1;
					});
				}).slice(0, 80);
				resultsEl.innerHTML = '';
				shown.forEach(function (item) {
					var button = document.createElement('button');
					button.type = 'button';
					button.className = 'sticker-result';
					button.dataset.stickerId = String(item.id);
					button.dataset.stickerName = item.name;
					button.dataset.stickerImage = item.image || '';
					if (item.image) {
						var image = document.createElement('img');
						image.src = 'img/skins/sticker.png';
						image.dataset.remoteSrc = item.image;
						image.alt = '';
						button.appendChild(image);
						loadRemoteImage(image);
					} else {
						var empty = document.createElement('span');
						empty.className = 'sticker-empty-icon';
						empty.textContent = '+';
						button.appendChild(empty);
					}
					var name = document.createElement('span');
					name.textContent = item.name;
					button.appendChild(name);
					resultsEl.appendChild(button);
				});
			};

			var stickerSlotsIn = function (scope) {
				return Array.prototype.slice.call(scope ? scope.querySelectorAll('.sticker-slot') : []);
			};

			var setStickerSlot = function (slot, id, name, image) {
				if (!slot) return;
				var input = slot.querySelector('[data-sticker-input]');
				var valueInput = slot.querySelector('[data-sticker-value]');
				var preview = slot.querySelector('[data-sticker-preview]');
				var plus = slot.querySelector('.sticker-plus');
				var label = slot.querySelector('[data-sticker-name]');
				var labelText = slot.querySelector('[data-sticker-name-text]');
				id = String(id || '0');
				image = image || '';
				if (input) input.value = id;
				if (valueInput) valueInput.value = id === '0' ? '0;0;0;0;0;0;0' : defaultStickerValueForClient(id);
				if (preview) {
					preview.src = 'img/skins/sticker.png';
					preview.dataset.remoteSrc = image;
					preview.hidden = id === '0' || !image;
					loadRemoteImage(preview);
				}
				if (plus) plus.hidden = id !== '0' && !!image;
				if (labelText) {
					labelText.textContent = id === '0' ? (slot.dataset.emptyLabel || <?= json_encode(t('sticker_slot'), JSON_UNESCAPED_UNICODE) ?>) : name;
				} else if (label) {
					label.textContent = id === '0' ? (slot.dataset.emptyLabel || <?= json_encode(t('sticker_slot'), JSON_UNESCAPED_UNICODE) ?>) : name;
				}
				syncStickerSettingsButton(slot);
			};
			var stickerInfoFromSlot = function (slot) {
				var input = slot ? slot.querySelector('[data-sticker-input]') : null;
				var preview = slot ? slot.querySelector('[data-sticker-preview]') : null;
				var label = slot ? (slot.querySelector('[data-sticker-name-text]') || slot.querySelector('[data-sticker-name]')) : null;
				return {
					id: input ? String(input.value || '0') : '0',
					name: label ? label.textContent : '',
					image: preview ? (preview.dataset.remoteSrc || '') : ''
				};
			};

			var syncStickerToolButtons = function (section) {
				if (!section) return;
				var fillButton = section.querySelector('[data-sticker-fill-all]');
				var clearButton = section.querySelector('[data-sticker-clear-all]');
				var unique = {};
				var hasSticker = false;
				stickerSlotsIn(section).forEach(function (slot) {
					var info = stickerInfoFromSlot(slot);
					if (info.id !== '0') {
						unique[info.id] = true;
						hasSticker = true;
					}
				});
				if (fillButton) fillButton.disabled = Object.keys(unique).length !== 1;
				if (clearButton) clearButton.disabled = !hasSticker;
			};

			document.querySelectorAll('.sticker-section').forEach(syncStickerToolButtons);

			document.addEventListener('click', function (event) {
				var fillAllButton = event.target.closest('[data-sticker-fill-all]');
				if (fillAllButton) {
					if (fillAllButton.disabled) return;
					var fillSection = fillAllButton.closest('.sticker-section');
					var slots = stickerSlotsIn(fillSection);
					var source = null;
					slots.some(function (slot) {
						var info = stickerInfoFromSlot(slot);
						if (info.id !== '0') {
							source = info;
							return true;
						}
						return false;
					});
					if (!source) return;
					slots.forEach(function (slot) {
						setStickerSlot(slot, source.id, source.name, source.image);
					});
					syncStickerToolButtons(fillSection);
					return;
				}

				var clearAllButton = event.target.closest('[data-sticker-clear-all]');
				if (clearAllButton) {
					if (clearAllButton.disabled) return;
					var clearSection = clearAllButton.closest('.sticker-section');
					stickerSlotsIn(clearSection).forEach(function (slot) {
						setStickerSlot(slot, '0', <?= json_encode(t('no_sticker'), JSON_UNESCAPED_UNICODE) ?>, '');
					});
					syncStickerToolButtons(clearSection);
					return;
				}

				var settingsButton = event.target.closest('[data-sticker-settings]');
				if (settingsButton) {
					if (settingsButton.disabled) return;
					openStickerAdvanced(settingsButton.closest('.sticker-slot'));
					return;
				}
				var openButton = event.target.closest('[data-sticker-open]');
				if (openButton) {
					activeStickerSlot = openButton.closest('.sticker-slot');
					setStickerUnderlay(openButton.closest('.modal'));
					loadStickers().then(function () {
						if (searchInput) searchInput.value = '';
						renderStickerResults();
						if (picker) {
							picker.show();
							setTimeout(markStickerBackdrop, 0);
						}
						setTimeout(function () { if (searchInput) searchInput.focus(); }, 150);
					});
					return;
				}

				var resultButton = event.target.closest('.sticker-result');
				if (resultButton && activeStickerSlot) {
					var id = resultButton.dataset.stickerId || '0';
					var name = resultButton.dataset.stickerName || <?= json_encode(t('no_sticker'), JSON_UNESCAPED_UNICODE) ?>;
					var image = resultButton.dataset.stickerImage || '';
					saveStickerChoice(activeStickerSlot, id).then(function (payload) {
						setStickerSlot(activeStickerSlot, id, name, image);
						var valueInput = activeStickerSlot.querySelector('[data-sticker-value]');
						if (valueInput && payload && payload.value) valueInput.value = payload.value;
						activeStickerSlot.dataset.savedStickerId = String(id || '0');
						syncStickerSettingsButton(activeStickerSlot);
						syncStickerToolButtons(activeStickerSlot.closest('.sticker-section'));
						if (picker) picker.hide();
					}).catch(function (error) {
						alert(error && error.message ? error.message : stickerSaveFailedMessage);
					});
				}			});

			document.querySelectorAll('[data-sticker-settings]').forEach(function (button) {
				syncStickerSettingsButton(button.closest('.sticker-slot'));
			});

			if (advancedEl) {
				advancedEl.addEventListener('shown.bs.modal', markStickerBackdrop);
				advancedEl.addEventListener('hidden.bs.modal', function () {
					setStickerUnderlay(null);
				});
				advancedEl.querySelectorAll('[data-sticker-param]').forEach(function (row) {
					var range = row.querySelector('[data-sticker-param-range]');
					var number = row.querySelector('[data-sticker-param-number]');
					if (range) {
						range.addEventListener('input', function () {
							normalizeAdvancedRow(row, range);
						});
					}
					if (number) {
						number.addEventListener('input', function () {
							var numeric = parseFloat(number.value);
							if (isFinite(numeric) && range) range.value = numeric;
						});
						number.addEventListener('change', function () {
							normalizeAdvancedRow(row, number);
						});
						normalizeAdvancedRow(row, number);
					}
				});
				var resetButton = advancedEl.querySelector('[data-sticker-advanced-reset]');
				if (resetButton) {
					resetButton.addEventListener('click', function () {
						setAdvancedControls(stickerDefaults);
					});
				}
			}

			if (advancedForm) {
				advancedForm.addEventListener('submit', function (event) {
					if (!window.fetch || !activeAdvancedSlot) return;
					event.preventDefault();
					setAdvancedControls(readAdvancedControls());
					var formData = new FormData(advancedForm);
					formData.append('ajax', '1');
					fetch(window.location.href, {
						method: 'POST',
						body: formData,
						headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
					}).then(function (response) {
						return response.ok ? response.json() : Promise.reject();
					}).then(function (payload) {
						if (!payload || !payload.ok) throw new Error(payload && payload.message ? payload.message : stickerSaveFailedMessage);
						var valueInput = activeAdvancedSlot.querySelector('[data-sticker-value]');
						if (valueInput) valueInput.value = payload.value || valueInput.value;
						var parts = parseStickerValue(payload.value || '');
						activeAdvancedSlot.dataset.savedStickerId = String(parts.id || '0');
						syncStickerSettingsButton(activeAdvancedSlot);
						if (advancedModal) advancedModal.hide();
					}).catch(function (error) {
						alert(error && error.message ? error.message : stickerSaveFailedMessage);
					});
				});
			}
			if (searchInput) {
				searchInput.addEventListener('input', renderStickerResults);
			}

			var validationMessages = {
				required: <?= json_encode(t('validation_required'), JSON_UNESCAPED_UNICODE) ?>,
				numberRange: <?= json_encode(t('validation_number_range'), JSON_UNESCAPED_UNICODE) ?>,
				integerRange: <?= json_encode(t('validation_integer_range'), JSON_UNESCAPED_UNICODE) ?>
			};

			var fillValidationMessage = function (template, input) {
				return template
					.replace('{min}', input.min || '0')
					.replace('{max}', input.max || '');
			};

			var validateLocalizedInput = function (input) {
				input.setCustomValidity('');
				if (input.validity.valueMissing) {
					input.setCustomValidity(validationMessages.required);
					return;
				}
				if (input.type === 'number' && (input.validity.rangeUnderflow || input.validity.rangeOverflow || input.validity.stepMismatch)) {
					var template = input.step && input.step !== 'any' ? validationMessages.integerRange : validationMessages.numberRange;
					input.setCustomValidity(fillValidationMessage(template, input));
				}
			};

			document.querySelectorAll('input[required], input[type="number"][min], input[type="number"][max], [data-nametag-input], [data-stattrak-input]').forEach(function (input) {
				input.addEventListener('invalid', function () {
					validateLocalizedInput(input);
				});
				input.addEventListener('input', function () {
					validateLocalizedInput(input);
				});
				input.addEventListener('change', function () {
					validateLocalizedInput(input);
				});
				validateLocalizedInput(input);
			});
			document.querySelectorAll('[data-nametag-toggle]').forEach(function (toggle) {
				var row = toggle.closest('.nametag-row');
				var input = row ? row.querySelector('[data-nametag-input]') : null;
				var sync = function () {
					if (!input) return;
					input.hidden = !toggle.checked;
					input.disabled = !toggle.checked;
					input.required = toggle.checked;
					validateLocalizedInput(input);
				};
				toggle.addEventListener('change', sync);
				sync();
			});
			document.querySelectorAll('[data-stattrak-toggle]').forEach(function (toggle) {
				var row = toggle.closest('.stattrak-row');
				var input = row ? row.querySelector('[data-stattrak-input]') : null;
				var sync = function () {
					if (!input) return;
					input.hidden = !toggle.checked;
					input.disabled = !toggle.checked;
					input.required = toggle.checked;
					validateLocalizedInput(input);
				};
				toggle.addEventListener('change', sync);
				sync();
			});

			var inspectImportEl = document.getElementById('inspectImportModal');
			if (inspectImportEl && window.bootstrap) {
				var inspectModal = new bootstrap.Modal(inspectImportEl);
				var inspectDefindexInput = inspectImportEl.querySelector('[data-inspect-defindex]');
				var inspectWeaponLabel = inspectImportEl.querySelector('[data-inspect-weapon-label]');
				var inspectLinkInput = inspectImportEl.querySelector('[data-inspect-input]');

				document.querySelectorAll('[data-inspect-import]').forEach(function (button) {
					button.addEventListener('click', function () {
						if (inspectDefindexInput) inspectDefindexInput.value = button.getAttribute('data-inspect-weapon-defindex') || '';
						if (inspectWeaponLabel) inspectWeaponLabel.textContent = button.getAttribute('data-inspect-weapon-name') || '';
						if (inspectLinkInput) inspectLinkInput.value = '';
						// La modale d'arme et celle d'import ne peuvent pas coexister :
						// on ferme la première avant d'ouvrir la seconde.
						var parentModalEl = button.closest('.modal');
						var parentModal = parentModalEl ? bootstrap.Modal.getInstance(parentModalEl) : null;
						if (parentModal) {
							parentModalEl.addEventListener('hidden.bs.modal', function open() {
								parentModalEl.removeEventListener('hidden.bs.modal', open);
								inspectModal.show();
							});
							parentModal.hide();
						} else {
							inspectModal.show();
						}
					});
				});

				inspectImportEl.addEventListener('shown.bs.modal', function () {
					if (inspectLinkInput) inspectLinkInput.focus();
				});
			}
		})();
	</script>
</body>

</html>













