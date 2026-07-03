<?php
require_once 'class/config.php';
require_once 'class/database.php';
require_once 'class/utils.php';

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
		'name_tag' => '名称标签',
		'stickers' => '贴纸',
		'choose_sticker' => '选择贴纸',
		'search_sticker' => '搜索贴纸',
		'no_sticker' => '无贴纸',
		'sticker_slot' => '贴纸槽位',
		'apply_sticker_to_all' => "\u{4E00}\u{952E}\u{8986}\u{76D6}\u{5168}\u{90E8}\u{8D34}\u{7EB8}\u{69FD}\u{4F4D}",
		'clear_all_stickers' => "\u{4E00}\u{952E}\u{6E05}\u{9664}\u{5168}\u{90E8}\u{8D34}\u{7EB8}",
		'access_title' => '访问密码',
		'access_prompt' => '请输入访问密码后继续进入网站',
		'access_password' => '密码',
		'access_unlock' => '进入网站',
		'access_invalid' => '密码不正确，请重试',
		'invalid_steamid' => '请输入正确的 Steam64 ID',
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
		'name_tag' => 'Name Tag',
		'stickers' => 'Stickers',
		'choose_sticker' => 'Choose Sticker',
		'search_sticker' => 'Search stickers',
		'no_sticker' => 'No sticker',
		'sticker_slot' => 'Sticker Slot',
		'apply_sticker_to_all' => 'Apply sticker to all slots',
		'clear_all_stickers' => 'Clear all stickers',
		'access_title' => 'Access Password',
		'access_prompt' => 'Please enter the access password to continue to the website.',
		'access_password' => 'Password',
		'access_unlock' => 'Enter Site',
		'access_invalid' => 'Incorrect password. Please try again.',
		'invalid_steamid' => 'Please enter a valid Steam64 ID.',
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
		`created_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY `uniq_steamid` (`steamid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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
		`weapon_nametag` VARCHAR(64) NULL,
		`updated_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY `uniq_skin_setting` (`steamid`, `weapon_team`, `weapon_defindex`, `weapon_paint_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	if (!columnExists($db, $skinSettingsTable, 'weapon_nametag')) {
		$db->query("ALTER TABLE `{$skinSettingsTable}` ADD `weapon_nametag` VARCHAR(64) NULL AFTER `weapon_stattrak`");
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

function stickerIdFromValue($value)
{
	$parts = explode(';', (string)$value);
	return max(0, (int)($parts[0] ?? 0));
}

function buildStickerValue($stickerId)
{
	$stickerId = max(0, (int)$stickerId);
	if ($stickerId === 0) {
		return defaultStickerValue();
	}
	return "{$stickerId};{$stickerId};0;0;0;1;0";
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
		$values[$i] = buildStickerValue($stickerId);
	}
	return $values;
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

function saveSkinSettingCache($db, $skinSettingsTable, $steamid, $team, $defindex, $paint, $wear, $seed, $stattrak, $nameTag)
{
	$db->query("INSERT INTO `{$skinSettingsTable}`
		(`steamid`, `weapon_team`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`)
		VALUES (:steamid, :team, :defindex, :paint, :wear, :seed, :stattrak, :nametag)
		ON DUPLICATE KEY UPDATE
			`weapon_wear` = :wear_update,
			`weapon_seed` = :seed_update,
			`weapon_stattrak` = :stattrak_update,
			`weapon_nametag` = :nametag_update", [
		"steamid" => $steamid,
		"team" => $team,
		"defindex" => $defindex,
		"paint" => $paint,
		"wear" => $wear,
		"seed" => $seed,
		"stattrak" => $stattrak,
		"nametag" => $nameTag,
		"wear_update" => $wear,
		"seed_update" => $seed,
		"stattrak_update" => $stattrak,
		"nametag_update" => $nameTag,
	]);
}

function loadSkinSettingCache($db, $skinSettingsTable, $steamid, $team, $defindex, $paint)
{
	$rows = $db->select("SELECT `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`
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

if ($accessGranted && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$postAction = $_POST['action'] ?? '';

	if ($postAction === 'create_preset') {
		$steamid = cleanSteamId($_POST['steamid'] ?? '');
		$nickname = trim((string)($_POST['nickname'] ?? ''));
		if (!preg_match('/^\d{5,32}$/', $steamid)) {
			$error = t('invalid_steamid');
			$action = 'new';
		} else {
			$existingPreset = findPreset($db, $presetTable, $steamid);
			$db->query("INSERT INTO `{$presetTable}` (`steamid`, `nickname`) VALUES (:steamid, :nickname)
				ON DUPLICATE KEY UPDATE `nickname` = VALUES(`nickname`)", [
				"steamid" => $steamid,
				"nickname" => $nickname !== '' ? $nickname : null,
			]);
			if ($existingPreset) {
				go('index.php?action=list&notice=updated_existing');
			}
			go('index.php?action=list');
		}
	}

	if ($postAction === 'delete_preset') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$preset = findPreset($db, $presetTable, $id);
		if ($preset) {
			$steamid = $preset['steamid'];
			foreach (['wp_player_skins', 'wp_player_knife', 'wp_player_agents', 'wp_player_gloves', 'wp_player_music'] as $table) {
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

		if (!$preset || !preg_match('/^\d{5,32}$/', $steamid)) {
			go("index.php?action=edit&id={$id}&team={$team}&error=identity");
		}

		$duplicate = $db->select("SELECT `id` FROM `{$presetTable}` WHERE `steamid` = :steamid AND `id` <> :id LIMIT 1", [
			"steamid" => $steamid,
			"id" => $preset['id'] ?? 0,
		]);
		if ($duplicate) {
			go("index.php?action=edit&id={$id}&team={$team}&error=identity");
		}

		$oldSteamid = $preset['steamid'];
		$db->query("UPDATE `{$presetTable}` SET `steamid` = :steamid, `nickname` = :nickname WHERE `steamid` = :old_steamid", [
			"steamid" => $steamid,
			"nickname" => $nickname !== '' ? $nickname : null,
			"old_steamid" => $oldSteamid,
		]);

		if ($oldSteamid !== $steamid) {
			foreach (['wp_player_skins', 'wp_player_knife', 'wp_player_agents', 'wp_player_gloves', 'wp_player_music'] as $table) {
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

	if ($postAction === 'save_skin') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$displayTeam = readTeam($team);
		$preset = findPreset($db, $presetTable, $id);
		if (!$preset) {
			go('index.php?action=list');
		}

		$steamid = $preset['steamid'];
		$weapons = UtilsClass::getWeaponsFromArray();
		$skins = UtilsClass::skinsFromJson();
		$knifes = UtilsClass::getKnifeTypes();
		$gloves = glovesFromJson();
		$stickers = stickersFromJson();
		$selectedRows = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`
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
					$existing = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4` FROM `wp_player_skins`
						WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
						"steamid" => $steamid,
						"weapon_defindex" => $gloveDefindex,
						"team" => $targetTeam,
					]);
					if ($existing) {
						$current = $existing[0];
						saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, (int)$current['weapon_paint_id'], (float)$current['weapon_wear'], (int)$current['weapon_seed'], 0, null);
					}

					$cached = loadSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, $paint);
					$wear = $cached ? (float)$cached['weapon_wear'] : 0.0;
					$seed = $cached ? (int)$cached['weapon_seed'] : 0;

					if ($existing) {
						$db->query("UPDATE `wp_player_skins`
							SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = 0, `weapon_nametag` = NULL
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
							(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_team`)
							VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 0, NULL, '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', :team)", [
							"steamid" => $steamid,
							"weapon_defindex" => $gloveDefindex,
							"weapon_paint_id" => $paint,
							"weapon_wear" => $wear,
							"weapon_seed" => $seed,
							"team" => $targetTeam,
						]);
					}
					saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, $paint, $wear, $seed, 0, null);
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

				$existing = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4` FROM `wp_player_skins`
					WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
					"steamid" => $steamid,
					"weapon_defindex" => $defindex,
					"team" => $targetTeam,
				]);
				if ($existing) {
					$current = $existing[0];
					saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, (int)$current['weapon_paint_id'], (float)$current['weapon_wear'], (int)$current['weapon_seed'], 0, null);
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
						SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = 0, `weapon_nametag` = NULL
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
						(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_team`)
						VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 0, NULL, '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', :team)", [
						"steamid" => $steamid,
						"weapon_defindex" => $defindex,
						"weapon_paint_id" => $paint,
						"weapon_wear" => $wear,
						"weapon_seed" => $seed,
						"team" => $targetTeam,
					]);
				}
				saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint, $wear, $seed, 0, null);
			}

		} elseif (isset($ex[0], $ex[1]) && array_key_exists((int)$ex[0], $weapons) && array_key_exists((int)$ex[1], $skins[(int)$ex[0]] ?? [])) {
			$defindex = (int)$ex[0];
			$paint = (int)$ex[1];
			$hasExplicitWear = array_key_exists('wear', $_POST);
			$hasExplicitSeed = array_key_exists('seed', $_POST);
			$submittedStickerValues = readStickerValuesFromPost(stickerSlotCount($defindex), $stickers);
			$hasExplicitSettings = $hasExplicitWear || $hasExplicitSeed || array_key_exists('stattrak', $_POST) || array_key_exists('nametag_present', $_POST) || $submittedStickerValues !== null;
			$submittedWear = $hasExplicitWear ? max(0.0, min(1.0, (float)$_POST['wear'])) : null;
			$submittedSeed = $hasExplicitSeed ? max(0, min(1000, (int)$_POST['seed'])) : null;
			$submittedStatTrak = array_key_exists('stattrak', $_POST) ? 1 : 0;
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

				$existing = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4` FROM `wp_player_skins`
					WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
					"steamid" => $steamid,
					"weapon_defindex" => $defindex,
					"team" => $targetTeam,
				]);
				if ($existing) {
					$current = $existing[0];
					saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, (int)$current['weapon_paint_id'], (float)$current['weapon_wear'], (int)$current['weapon_seed'], (int)$current['weapon_stattrak'], $current['weapon_nametag']);
				}

				if ($hasExplicitSettings) {
					$wear = $submittedWear ?? ($existing[0]['weapon_wear'] ?? 0.0);
					$seed = $submittedSeed ?? ($existing[0]['weapon_seed'] ?? 0);
					$stattrak = $submittedStatTrak;
					$nameTag = array_key_exists('nametag_present', $_POST) ? $submittedNameTag : ($existing[0]['weapon_nametag'] ?? null);
					$stickerValues = $submittedStickerValues ?? ($existing ? stickerValuesFromRow($existing[0]) : defaultStickerValues());
				} else {
					$cached = loadSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint);
					$wear = $cached ? (float)$cached['weapon_wear'] : 0.0;
					$seed = $cached ? (int)$cached['weapon_seed'] : 0;
					$stattrak = $cached ? (int)$cached['weapon_stattrak'] : 0;
					$nameTag = $cached ? $cached['weapon_nametag'] : null;
					$stickerValues = $existing ? stickerValuesFromRow($existing[0]) : defaultStickerValues();
				}

				if ($existing) {
					$db->query("UPDATE `wp_player_skins`
						SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = :weapon_stattrak, `weapon_nametag` = :weapon_nametag, `weapon_sticker_0` = :weapon_sticker_0, `weapon_sticker_1` = :weapon_sticker_1, `weapon_sticker_2` = :weapon_sticker_2, `weapon_sticker_3` = :weapon_sticker_3, `weapon_sticker_4` = :weapon_sticker_4
						WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
						"steamid" => $steamid,
						"weapon_defindex" => $defindex,
						"weapon_paint_id" => $paint,
						"weapon_wear" => $wear,
						"weapon_seed" => $seed,
						"weapon_stattrak" => $stattrak,
						"weapon_nametag" => $nameTag,
						"weapon_sticker_0" => $stickerValues[0],
						"weapon_sticker_1" => $stickerValues[1],
						"weapon_sticker_2" => $stickerValues[2],
						"weapon_sticker_3" => $stickerValues[3],
						"weapon_sticker_4" => $stickerValues[4],
						"team" => $targetTeam,
					]);
				} else {
					$db->query("INSERT INTO `wp_player_skins`
						(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_team`)
						VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, :weapon_stattrak, :weapon_nametag, :weapon_sticker_0, :weapon_sticker_1, :weapon_sticker_2, :weapon_sticker_3, :weapon_sticker_4, :team)", [
						"steamid" => $steamid,
						"weapon_defindex" => $defindex,
						"weapon_paint_id" => $paint,
						"weapon_wear" => $wear,
						"weapon_seed" => $seed,
						"weapon_stattrak" => $stattrak,
						"weapon_nametag" => $nameTag,
						"weapon_sticker_0" => $stickerValues[0],
						"weapon_sticker_1" => $stickerValues[1],
						"weapon_sticker_2" => $stickerValues[2],
						"weapon_sticker_3" => $stickerValues[3],
						"weapon_sticker_4" => $stickerValues[4],
						"team" => $targetTeam,
					]);
				}
				saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint, $wear, $seed, $stattrak, $nameTag);
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
		if (!$preset || $team !== 1 || !tableExists($db, 'wp_player_music') || !array_key_exists($musicId, $music)) {
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

	if ($postAction === 'save_agent') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		if (!$preset || !in_array($team, [2, 3], true) || !tableExists($db, 'wp_player_agents')) {
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

if ($action === 'edit') {
	$id = cleanSteamId($_GET['id'] ?? '');
	$displayTeam = readTeam($team);
	$currentPreset = findPreset($db, $presetTable, $id);
	if (!$currentPreset) {
		go('index.php?action=list');
	}

	$steamid = $currentPreset['steamid'];
	$weapons = UtilsClass::getWeaponsFromArray();
	$skins = UtilsClass::skinsFromJson();
	$gloves = glovesFromJson();
	$stickers = stickersFromJson();
	$music = musicFromJson();
	$selectedRows = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`
		FROM `wp_player_skins`
		WHERE `steamid` = :steamid AND `weapon_team` = :team", [
		"steamid" => $steamid,
		"team" => $displayTeam,
	]);
	$selectedSkins = UtilsClass::getSelectedSkins($selectedRows);
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
			<section class="panel narrow">
				<h1><?= h(t('new_preset')) ?></h1>
				<?php if ($error) : ?><div class="alert alert-danger"><?= h(t('save_failed')) ?></div><?php endif; ?>
				<form method="post" class="form-grid">
					<input type="hidden" name="action" value="create_preset">
					<label>Steam64 ID
						<input class="form-control" name="steamid" inputmode="numeric" autocomplete="off" required>
					</label>
					<label><?= h(t('nickname')) ?>
						<input class="form-control" name="nickname" autocomplete="off" placeholder="<?= h(t('nickname_placeholder')) ?>">
					</label>
					<button class="btn btn-primary" type="submit"><?= h(t('save')) ?></button>
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
						</div>
						<div class="preset-actions">
							<a class="btn btn-outline-light" href="index.php?action=edit&id=<?= h($preset['steamid']) ?>&team=1"><?= h(t('edit')) ?></a>
							<form method="post" onsubmit="return confirm(<?= h(json_encode(t('delete_confirm'), JSON_UNESCAPED_UNICODE)) ?>);">
								<input type="hidden" name="action" value="delete_preset">
								<input type="hidden" name="id" value="<?= h($preset['steamid']) ?>">
								<button class="btn btn-outline-danger" type="submit"><?= h(t('delete')) ?></button>
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
			<?php if (isset($_GET['error'])) : ?><div class="alert alert-danger"><?= h(t('save_failed')) ?></div><?php endif; ?>

			<section class="panel">
				<form method="post" class="identity-form">
					<input type="hidden" name="action" value="save_identity">
					<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
					<input type="hidden" name="team" value="<?= $team ?>">
					<label>Steam64 ID
						<input class="form-control" name="steamid" value="<?= h($currentPreset['steamid']) ?>" inputmode="numeric" required>
					</label>
					<label><?= h(t('nickname')) ?>
						<input class="form-control" name="nickname" value="<?= h($currentPreset['nickname'] ?? '') ?>">
					</label>
					<button class="btn btn-primary" type="submit"><?= h(t('save')) ?></button>
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
					$currentKnifeNameTag = $selectedKnifeSkin['weapon_nametag'] ?? null;
					if ($actualKnifeKey > 0) {
						$cachedKnifeSetting = loadSkinSettingCache($db, $skinSettingsTable, $currentPreset['steamid'], $displayTeam, $actualKnifeKey, $currentKnifePaintId);
						if ($cachedKnifeSetting) {
							$currentKnifeWear = $cachedKnifeSetting['weapon_wear'];
							$currentKnifeSeed = $cachedKnifeSetting['weapon_seed'];
							$currentKnifeStatTrak = (int)$cachedKnifeSetting['weapon_stattrak'];
							$currentKnifeNameTag = $cachedKnifeSetting['weapon_nametag'];
						}
					}
					$currentKnifeNameTagEnabled = $currentKnifeNameTag !== null && $currentKnifeNameTag !== '';
					?>
					<?php if ($currentKnifeStatTrak) : ?><span class="stattrak-badge">StatTrak™</span><?php endif; ?>
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
												<div class="col-12">
													<label class="check-line">
														<input type="checkbox" name="stattrak" value="1" <?= $currentKnifeStatTrak ? 'checked' : '' ?>>
														<span class="stattrak-label">StatTrak™</span>
													</label>
												</div>
											</div>
										<?php endif; ?>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('close')) ?></button>
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
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('close')) ?></button>
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
										<h5 class="modal-title"><?= h(t('select')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<div class="skin-picker-grid">
											<?php foreach ($music as $musicId => $musicKit) : ?>
												<?php $musicImage = (string)($musicKit['image'] ?? ''); ?>
												<button type="submit" name="music_id" value="<?= (int)$musicId ?>" class="skin-result <?= $currentMusicId === (int)$musicId ? 'active' : '' ?>">
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
					$initialNameTagValue = $hasSkin ? ($selectedSkins[$defindex]['weapon_nametag'] ?? null) : null;
					$initialStickerValues = $hasSkin ? stickerValuesFromRow($selectedSkins[$defindex]) : defaultStickerValues();
					$stickerSlotTotal = stickerSlotCount((int)$defindex);
					if ($hasSkin) {
						$cachedSkinSetting = loadSkinSettingCache($db, $skinSettingsTable, $currentPreset['steamid'], $displayTeam, (int)$defindex, $currentPaintId);
						if ($cachedSkinSetting) {
							$initialWearValue = $cachedSkinSetting['weapon_wear'];
							$initialSeedValue = $cachedSkinSetting['weapon_seed'];
							$initialStatTrakValue = (int)$cachedSkinSetting['weapon_stattrak'];
							$initialNameTagValue = $cachedSkinSetting['weapon_nametag'];
						}
					}
					$initialNameTagEnabled = $initialNameTagValue !== null && $initialNameTagValue !== '';
					$initialStickerIds = array_map('stickerIdFromValue', $initialStickerValues);
					$modalId = "weaponModal{$defindex}";
					$skinPickerId = "skinPicker{$defindex}";
					?>
					<div class="skin-card weapon-card">
						<?php if ($initialStatTrakValue) : ?><span class="stattrak-badge">StatTrak™</span><?php endif; ?>
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
												<div class="col-12">
													<label class="check-line">
														<input type="checkbox" name="stattrak" value="1" <?= $initialStatTrakValue ? 'checked' : '' ?>>
														<span class="stattrak-label">StatTrak™</span>
													</label>
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
															$currentStickerId = $initialStickerIds[$slotIndex] ?? 0;
															$currentSticker = $stickers[$currentStickerId] ?? $stickers[0];
															?>
															<div class="sticker-slot" data-empty-label="<?= h(t('sticker_slot') . ' ' . ($slotIndex + 1)) ?>">
																<input type="hidden" name="sticker_<?= $slotIndex ?>" value="<?= (int)$currentStickerId ?>" data-sticker-input>
																<button type="button" class="sticker-slot-button" data-sticker-open aria-label="<?= h(t('choose_sticker')) ?>">
																	<span class="sticker-plus sticker-empty-icon" <?= $currentStickerId > 0 ? 'hidden' : '' ?>>+</span>
																	<img src="img/skins/sticker.png" data-remote-src="<?= h($currentSticker['image'] ?? '') ?>" alt="" data-sticker-preview <?= $currentStickerId > 0 ? '' : 'hidden' ?>>
																</button>
																<div class="sticker-slot-name" data-sticker-name><span data-sticker-name-text><?= h($currentStickerId > 0 ? ($currentSticker['name'] ?? '') : t('sticker_slot') . ' ' . ($slotIndex + 1)) ?></span></div>
															</div>
														<?php endfor; ?>
													</div>
												</div>
											</div>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('close')) ?></button>
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
			document.querySelectorAll('.skin-picker-modal').forEach(function (modal) {
				modal.addEventListener('show.bs.modal', function () {
					modal.querySelectorAll('img[data-picker-remote-src]').forEach(function (image) {
						image.dataset.remoteSrc = image.dataset.pickerRemoteSrc || '';
						image.removeAttribute('data-picker-remote-src');
						loadRemoteImage(image);
					});
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
				var preview = slot.querySelector('[data-sticker-preview]');
				var plus = slot.querySelector('.sticker-plus');
				var label = slot.querySelector('[data-sticker-name]');
				var labelText = slot.querySelector('[data-sticker-name-text]');
				id = String(id || '0');
				image = image || '';
				if (input) input.value = id;
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
					setStickerSlot(activeStickerSlot, id, name, image);
					syncStickerToolButtons(activeStickerSlot.closest('.sticker-section'));
					if (picker) picker.hide();
				}
			});

			if (searchInput) {
				searchInput.addEventListener('input', renderStickerResults);
			}

			document.querySelectorAll('[data-nametag-toggle]').forEach(function (toggle) {
				var row = toggle.closest('.nametag-row');
				var input = row ? row.querySelector('[data-nametag-input]') : null;
				var sync = function () {
					if (!input) return;
					input.hidden = !toggle.checked;
					input.disabled = !toggle.checked;
					input.required = toggle.checked;
				};
				toggle.addEventListener('change', sync);
				sync();
			});
		})();
	</script>
</body>

</html>
