<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$target = $root . '/preview/website';

function removeDirectory(string $path): void
{
	if (!is_dir($path)) {
		return;
	}
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($items as $item) {
		$item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
	}
	@rmdir($path);
}

function copyDirectory(string $source, string $destination): void
{
	if (!is_dir($source)) {
		return;
	}
	if (!is_dir($destination)) {
		mkdir($destination, 0777, true);
	}
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ($items as $item) {
		$targetPath = $destination . DIRECTORY_SEPARATOR . $items->getSubPathName();
		if ($item->isDir()) {
			if (!is_dir($targetPath)) {
				mkdir($targetPath, 0777, true);
			}
		} else {
			copy($item->getPathname(), $targetPath);
		}
	}
}

function writeFile(string $path, string $content): void
{
	$dir = dirname($path);
	if (!is_dir($dir)) {
		mkdir($dir, 0777, true);
	}
	file_put_contents($path, $content);
}

removeDirectory($target);
if (!is_dir($target)) {
	mkdir($target, 0777, true);
}

copy($root . '/style.css', $target . '/style.css');
if (is_file($root . '/favicon.svg')) {
	copy($root . '/favicon.svg', $target . '/favicon.svg');
}
copyDirectory($root . '/data', $target . '/data');
copyDirectory($root . '/img', $target . '/img');

$previewCss = <<<'CSS'

.preview-note {
	color: #ff8a8a;
	font-size: 13px;
	margin-top: 6px;
	padding: 8px 10px;
	background: rgba(255, 75, 75, .1);
	border: 1px solid rgba(255, 75, 75, .35);
	border-radius: 6px;
}

.preview-static-link {
	pointer-events: none;
}

.preview-picker-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
	gap: 10px;
}

.preview-result {
	display: grid;
	grid-template-rows: 132px auto;
	gap: 10px;
	align-items: center;
	justify-items: center;
	min-height: 188px;
	padding: 12px;
	background: #0d1016;
	border: 1px solid var(--line);
	border-radius: 8px;
	color: var(--text);
	font-size: 12px;
	line-height: 1.25;
	text-align: center;
	cursor: pointer;
}

.preview-result:hover,
.preview-result.active {
	border-color: var(--primary);
	color: var(--text);
}

.preview-result.active {
	box-shadow: 0 0 0 1px rgba(79, 140, 255, .35);
}

.preview-result img {
	width: 100%;
	height: 132px;
	object-fit: contain;
}

.preview-modal[hidden] {
	display: none;
}

.preview-modal {
	position: fixed;
	inset: 0;
	z-index: 1060;
	display: grid;
	place-items: center;
	padding: 18px;
	background: rgba(0, 0, 0, .64);
}

#pickerModal {
	z-index: 1080;
}

.preview-modal-panel {
	width: min(960px, 100%);
	max-height: min(820px, calc(100vh - 36px));
	overflow: auto;
	background: var(--panel);
	border: 1px solid var(--line);
	border-radius: 8px;
}

.preview-modal-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	padding: 14px;
	border-bottom: 1px solid var(--line);
}

.preview-modal-head h2 {
	margin: 0;
	font-size: 18px;
}

.preview-modal-body {
	padding: 14px;
}

.preview-close {
	border: 0;
	background: transparent;
	color: var(--text);
	font-size: 24px;
	line-height: 1;
	cursor: pointer;
}

.preview-actions {
	display: flex;
	gap: 8px;
	flex-wrap: wrap;
}

.preview-edit-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 12px;
}

.preview-edit-grid .check-line {
	grid-column: 1 / -1;
}

.preview-sticker-section {
	grid-column: 1 / -1;
	display: grid;
	gap: 8px;
}

.preview-sticker-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(72px, 1fr));
	gap: 8px;
}

.preview-sticker-slot {
	display: grid;
	gap: 6px;
	min-width: 0;
}

.preview-sticker-slot button {
	width: 100%;
	height: 78px;
	display: grid;
	place-items: center;
	background: #0d1016;
	border: 1px solid var(--line);
	border-radius: 8px;
	color: var(--muted);
	cursor: pointer;
	padding: 8px;
}

.preview-sticker-slot button:hover {
	border-color: var(--primary);
	color: var(--text);
}

.preview-sticker-slot img {
	width: 54px;
	height: 54px;
	object-fit: contain;
}

.preview-sticker-slot span {
	color: var(--muted);
	font-size: 12px;
	line-height: 1.25;
	text-align: center;
	overflow-wrap: anywhere;
}

@media (max-width: 576px) {
	.preview-edit-grid {
		grid-template-columns: 1fr;
	}
}
CSS;

file_put_contents($target . '/style.css', $previewCss, FILE_APPEND);

$indexHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>CS2 WeaponPaints Loadout Preview</title>
	<link rel="icon" href="favicon.svg" type="image/svg+xml">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="style.css">
</head>
<body>
	<main class="app-shell">
		<header class="page-head">
			<div>
				<a class="back-link preview-static-link" href="#" id="backLink"></a>
				<h1 id="pageTitle"></h1>
				<p id="pageSubtitle"></p>
				<p class="preview-note" id="previewNote"></p>
			</div>
			<nav class="team-tabs" id="teamTabs"></nav>
		</header>

		<section class="panel">
			<form class="identity-form" id="identityForm">
				<label>Steam64 ID
					<input class="form-control" id="steamid" inputmode="numeric" required>
				</label>
				<label id="nicknameLabel"></label>
				<button class="btn btn-primary" type="submit" id="saveIdentity"></button>
			</form>
		</section>

		<div class="card-grid" id="cardGrid"></div>

		<nav class="language-switch" aria-label="Language">
			<details class="language-menu">
				<summary class="language-button" aria-label="Language" title="Language">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<circle cx="12" cy="12" r="9"></circle>
						<path d="M3 12h18M12 3c2.4 2.7 3.6 5.7 3.6 9s-1.2 6.3-3.6 9M12 3c-2.4 2.7-3.6 5.7-3.6 9s1.2 6.3 3.6 9"></path>
					</svg>
				</summary>
				<div class="language-dropdown">
					<a href="#" data-language="zh-CN">简体中文</a>
					<a href="#" data-language="en">English</a>
				</div>
			</details>
		</nav>
	</main>

	<div class="preview-modal" id="pickerModal" hidden>
		<div class="preview-modal-panel">
			<div class="preview-modal-head">
				<h2 id="pickerTitle"></h2>
				<button class="preview-close" type="button" id="pickerClose" aria-label="Close">×</button>
			</div>
			<div class="preview-modal-body">
				<div class="preview-picker-grid" id="pickerGrid"></div>
			</div>
		</div>
	</div>

	<div class="preview-modal" id="editModal" hidden>
		<div class="preview-modal-panel">
			<form id="editForm">
				<div class="preview-modal-head">
					<h2 id="editTitle"></h2>
					<button class="preview-close" type="button" id="editClose" aria-label="Close">×</button>
				</div>
				<div class="preview-modal-body">
					<div class="preview-edit-grid" id="editFields"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" id="editCancel"></button>
					<button type="submit" class="btn btn-primary" id="editSave"></button>
				</div>
			</form>
		</div>
	</div>

	<script src="preview.js"></script>
</body>
</html>
HTML;

$previewJs = <<<'JS'
(function () {
	'use strict';

	var storageKey = 'cs2_wp_static_preview_v1';
	var data = {};
	var state = loadState();

	var ui = {
		'zh-CN': {
			backList: '返回配置列表',
			editLoadout: '编辑配置',
			nickname: '备注用户名',
			save: '保存',
			select: '选择',
			category: '类别',
			skin: '皮肤',
			edit: '编辑',
			settings: '设置',
			close: '关闭',
			chooseCategory: '选择类别',
			chooseSkin: '选择皮肤',
			global: '全局',
			t: 'T 阵营',
			ct: 'CT 阵营',
			knife: '匕首',
			gloves: '手套',
			tAgent: 'T 探员',
			ctAgent: 'CT 探员',
			music: '音乐盒',
			wear: '磨损',
			pattern: '模板',
			stickers: '贴纸',
			chooseSticker: '选择贴纸',
			noSticker: '无贴纸',
			stickerSlot: '贴纸槽位',
			previewNote: 'GitHub 静态预览版：所有更改仅保存在当前浏览器 localStorage 中，不会写入数据库。原网站使用 PHP，GitHub 仅允许托管静态 HTML，因此这个 HTML 预览版可能缺少部分动画与功能。',
			useInventoryKnife: '使用库存匕首',
			useInventoryGloves: '使用库存手套',
			useInventoryAgent: '使用库存探员',
			useInventoryMusic: '使用库存音乐盒'
		},
		en: {
			backList: 'Back to Loadouts',
			editLoadout: 'Edit Loadout',
			nickname: 'Nickname',
			save: 'Save',
			select: 'Select',
			category: 'Category',
			skin: 'Skin',
			edit: 'Edit',
			settings: 'Settings',
			close: 'Close',
			chooseCategory: 'Choose Category',
			chooseSkin: 'Choose Skin',
			global: 'Global',
			t: 'T',
			ct: 'CT',
			knife: 'Knife',
			gloves: 'Gloves',
			tAgent: 'T Agent',
			ctAgent: 'CT Agent',
			music: 'Music Kit',
			wear: 'Wear Rating',
			pattern: 'Pattern Template',
			stickers: 'Stickers',
			chooseSticker: 'Choose Sticker',
			noSticker: 'No sticker',
			stickerSlot: 'Sticker Slot',
			previewNote: 'GitHub static preview: all changes are saved only in this browser localStorage and are never written to a database. The original website uses PHP, while GitHub only hosts static HTML, so this HTML preview may miss some animations and features.',
			useInventoryKnife: 'Use inventory knife',
			useInventoryGloves: 'Use inventory gloves',
			useInventoryAgent: 'Use inventory agent',
			useInventoryMusic: 'Use inventory music kit'
		}
	};

	var defaultWeapons = [
		7, 8, 9, 11, 13, 14, 16, 17, 19, 23, 24, 25, 26, 27, 28, 29, 30, 32, 33, 34, 35, 36, 38, 39, 40, 60, 61, 63, 64
	];

	var knifeDefindexes = [500, 503, 505, 506, 507, 508, 509, 512, 514, 515, 516, 517, 518, 519, 520, 521, 522, 523, 525, 526];
	var gloveTypeImages = {
		4725: 'img/skins/studded_brokenfang_gloves.png',
		5027: 'img/skins/studded_bloodhound_gloves.png',
		5030: 'img/skins/sporty_gloves.png',
		5031: 'img/skins/slick_gloves.png',
		5032: 'img/skins/leather_handwraps.png',
		5033: 'img/skins/motorcycle_gloves.png',
		5034: 'img/skins/specialist_gloves.png',
		5035: 'img/skins/studded_hydra_gloves.png'
	};

	function loadState() {
		var fallback = {
			language: 'zh-CN',
			team: 1,
			steamid: '12345678',
			nickname: 'Example',
			weapons: {},
			knife: { type: 0, paint: 0, wear: 0, seed: 0 },
			gloves: { type: 0, paint: 0, wear: 0, seed: 0 },
			agents: { 2: '', 3: '' },
			music: '0'
		};
		try {
			return Object.assign(fallback, JSON.parse(localStorage.getItem(storageKey) || '{}'));
		} catch (error) {
			return fallback;
		}
	}

	function saveState() {
		localStorage.setItem(storageKey, JSON.stringify(state));
	}

	function t(key) {
		return (ui[state.language] || ui['zh-CN'])[key] || key;
	}

	function dataFile(name) {
		return 'data/' + name + '_' + state.language + '.json';
	}

	function fetchJson(path) {
		return fetch(path, { cache: 'force-cache' }).then(function (response) {
			if (!response.ok) throw new Error(path);
			return response.json();
		});
	}

	function groupBy(items, key) {
		return items.reduce(function (groups, item) {
			var value = String(item[key] || 0);
			if (!groups[value]) groups[value] = [];
			groups[value].push(item);
			return groups;
		}, {});
	}

	function byPaint(items, paint) {
		paint = String(paint || 0);
		return (items || []).find(function (item) { return String(item.paint || 0) === paint; }) || (items || [])[0] || null;
	}

	function byWeapon(defindex) {
		return data.skinsByDef[String(defindex)] || [];
	}

	function firstDefaultSkin(items, fallbackName) {
		return items.find(function (item) { return String(item.paint || 0) === '0'; }) || {
			weapon_defindex: 0,
			weapon_name: fallbackName,
			paint: 0,
			paint_name: fallbackName,
			image: ''
		};
	}

	function categoryNameFromPaintName(name) {
		name = String(name || '');
		return name.indexOf('|') !== -1 ? name.split('|')[0].trim() : name;
	}

	function stickerSlotCount(defindex) {
		return [4, 9, 61, 64, 23].indexOf(Number(defindex)) !== -1 ? 5 : 4;
	}

	function stickerById(id) {
		return data.stickersById[String(id || 0)] || { id: '0', name: t('noSticker'), image: '' };
	}

	function itemImage(item, placeholder) {
		return item && item.image ? item.image : placeholder;
	}

	function weaponPlaceholder(weaponName) {
		if (!weaponName) return '';
		if (weaponName === 'weapon_knife') return 'img/skins/knife.png';
		return 'img/weapon/' + weaponName + '.png';
	}

	function createEl(tag, className, text) {
		var el = document.createElement(tag);
		if (className) el.className = className;
		if (text !== undefined) el.textContent = text;
		return el;
	}

	function imageEl(src, alt) {
		var img = createEl('img', 'skin-image');
		img.src = src || 'img/skins/sticker.png';
		img.alt = alt || '';
		return img;
	}

	function renderHeader() {
		var teams = { 1: t('global'), 2: t('t'), 3: t('ct') };
		document.documentElement.lang = state.language;
		document.getElementById('backLink').textContent = t('backList');
		document.getElementById('pageTitle').textContent = t('editLoadout');
		document.getElementById('pageSubtitle').textContent = state.nickname + ' · ' + teams[state.team];
		document.getElementById('previewNote').textContent = t('previewNote');
		document.getElementById('nicknameLabel').innerHTML = t('nickname') + '<input class="form-control" id="nickname" value="">';
		document.getElementById('steamid').value = state.steamid;
		document.getElementById('nickname').value = state.nickname;
		document.getElementById('saveIdentity').textContent = t('save');
		var tabs = document.getElementById('teamTabs');
		tabs.innerHTML = '';
		[1, 2, 3].forEach(function (team) {
			var link = createEl('a', state.team === team ? 'active' : '', teams[team]);
			link.href = '#';
			link.addEventListener('click', function (event) {
				event.preventDefault();
				state.team = team;
				saveState();
				render();
			});
			tabs.appendChild(link);
		});
		document.querySelectorAll('[data-language]').forEach(function (link) {
			link.classList.toggle('active', link.dataset.language === state.language);
		});
	}

	function card(title, name, image, options) {
		options = options || {};
		var el = createEl('div', 'skin-card' + (options.featured ? ' featured' : '') + (options.extraClass ? ' ' + options.extraClass : ''));
		if (options.stattrak) el.appendChild(createEl('span', 'stattrak-badge', 'StatTrak™'));
		var titleWrap = createEl('div', 'card-title-wrap');
		titleWrap.appendChild(createEl('span', '', title));
		titleWrap.appendChild(createEl('h2', '', name));
		el.appendChild(titleWrap);
		el.appendChild(imageEl(image, name));
		if (options.stickers && options.stickers.length) {
			var stickers = createEl('div', 'card-stickers');
			options.stickers.filter(function (id) { return String(id || 0) !== '0'; }).forEach(function (id) {
				var item = stickerById(id);
				var sticker = document.createElement('img');
				sticker.src = item.image || 'img/skins/sticker.png';
				sticker.alt = item.name || '';
				sticker.title = item.name || '';
				stickers.appendChild(sticker);
			});
			if (stickers.children.length) el.appendChild(stickers);
		}
		if (options.meta) {
			var meta = createEl('div', 'skin-meta');
			meta.appendChild(createEl('span', '', t('wear') + ' ' + options.meta.wear));
			meta.appendChild(createEl('span', '', t('pattern') + ' ' + options.meta.seed));
			el.appendChild(meta);
		}
		var actions = createEl('div', 'settings-row');
		(options.actions || []).forEach(function (action) {
			var button = createEl('button', 'btn btn-sm btn-outline-light', action.label);
			button.type = 'button';
			if (action.disabled) button.disabled = true;
			button.addEventListener('click', action.onClick);
			actions.appendChild(button);
		});
		el.appendChild(actions);
		return el;
	}

	function openPicker(title, items, selectedValue, onSelect, imageResolver, labelResolver, valueResolver) {
		var modal = document.getElementById('pickerModal');
		var grid = document.getElementById('pickerGrid');
		document.getElementById('pickerTitle').textContent = title;
		grid.innerHTML = '';
		items.forEach(function (item) {
			var value = valueResolver(item);
			var button = createEl('button', 'preview-result' + (String(value) === String(selectedValue) ? ' active' : ''));
			button.type = 'button';
			var img = document.createElement('img');
			img.src = imageResolver(item) || 'img/skins/sticker.png';
			img.alt = '';
			button.appendChild(img);
			button.appendChild(createEl('span', '', labelResolver(item)));
			button.addEventListener('click', function () {
				onSelect(item);
				saveState();
				closePicker();
				render();
			});
			grid.appendChild(button);
		});
		modal.hidden = false;
	}

	function closePicker() {
		document.getElementById('pickerModal').hidden = true;
	}

	function openEdit(title, target, options) {
		options = options || {};
		var modal = document.getElementById('editModal');
		var fields = document.getElementById('editFields');
		document.getElementById('editTitle').textContent = title + ' ' + t('settings');
		document.getElementById('editCancel').textContent = t('close');
		document.getElementById('editSave').textContent = t('save');
		fields.innerHTML = '';

		var wearLabel = createEl('label', '', t('wear'));
		var wearInput = createEl('input', 'form-control');
		wearInput.type = 'number';
		wearInput.step = 'any';
		wearInput.min = '0';
		wearInput.max = '1';
		wearInput.name = 'wear';
		wearInput.value = target.wear || 0;
		wearLabel.appendChild(wearInput);
		fields.appendChild(wearLabel);

		var seedLabel = createEl('label', '', t('pattern'));
		var seedInput = createEl('input', 'form-control');
		seedInput.type = 'number';
		seedInput.min = '0';
		seedInput.max = '1000';
		seedInput.name = 'seed';
		seedInput.value = target.seed || 0;
		seedLabel.appendChild(seedInput);
		fields.appendChild(seedLabel);

		if (options.stattrak) {
			var checkLabel = createEl('label', 'check-line');
			var check = createEl('input');
			check.type = 'checkbox';
			check.name = 'stattrak';
			check.checked = !!target.stattrak;
			checkLabel.appendChild(check);
			checkLabel.appendChild(createEl('span', 'stattrak-label', 'StatTrak™'));
			fields.appendChild(checkLabel);
		}

		if (options.stickers) {
			if (!Array.isArray(target.stickers)) target.stickers = [];
			while (target.stickers.length < options.stickerSlots) target.stickers.push(0);
			var stickerSection = createEl('div', 'preview-sticker-section');
			stickerSection.appendChild(createEl('div', 'sticker-section-title', t('stickers')));
			var stickerGrid = createEl('div', 'preview-sticker-grid');
			for (var slotIndex = 0; slotIndex < options.stickerSlots; slotIndex++) {
				(function (index) {
					var slot = createEl('div', 'preview-sticker-slot');
					var button = createEl('button');
					button.type = 'button';
					var sticker = stickerById(target.stickers[index]);
					if (String(sticker.id || 0) !== '0' && sticker.image) {
						var img = document.createElement('img');
						img.src = sticker.image;
						img.alt = sticker.name || '';
						button.appendChild(img);
					} else {
						button.appendChild(createEl('span', 'sticker-empty-icon', '+'));
					}
					button.addEventListener('click', function () {
						var stickerItems = [{ id: '0', name: t('noSticker'), image: '' }].concat(data.stickers);
						openPicker(t('chooseSticker'), stickerItems, target.stickers[index] || 0, function (item) {
							target.stickers[index] = item.id || '0';
							saveState();
							closeEdit();
							render();
						}, function (item) { return item.image || 'img/skins/sticker.png'; }, function (item) { return item.name; }, function (item) { return item.id || '0'; });
					});
					slot.appendChild(button);
					slot.appendChild(createEl('span', '', String(sticker.id || 0) !== '0' ? sticker.name : t('stickerSlot') + ' ' + (index + 1)));
					stickerGrid.appendChild(slot);
				})(slotIndex);
			}
			stickerSection.appendChild(stickerGrid);
			fields.appendChild(stickerSection);
		}

		var form = document.getElementById('editForm');
		form.onsubmit = function (event) {
			event.preventDefault();
			target.wear = Math.max(0, Math.min(1, parseFloat(wearInput.value) || 0));
			target.seed = Math.max(0, Math.min(1000, parseInt(seedInput.value, 10) || 0));
			if (options.stattrak) target.stattrak = !!form.elements.stattrak.checked;
			saveState();
			closeEdit();
			render();
		};
		modal.hidden = false;
	}

	function closeEdit() {
		document.getElementById('editModal').hidden = true;
	}

	function renderKnife(grid) {
		var type = Number(state.knife.type || 0);
		var skins = byWeapon(type);
		var current = type > 0 ? byPaint(skins, state.knife.paint) : { paint_name: t('useInventoryKnife'), image: '', weapon_name: 'weapon_knife' };
		var typeItems = [{ weapon_defindex: 0, weapon_name: 'weapon_knife', paint_name: t('useInventoryKnife'), image: '' }].concat(data.knifeTypes);
		grid.appendChild(card(t('knife'), current.paint_name, itemImage(current, weaponPlaceholder(type > 0 ? current.weapon_name : 'weapon_knife')), {
			featured: true,
			extraClass: 'loadout-card',
			meta: { wear: state.knife.wear || 0, seed: state.knife.seed || 0 },
			actions: [
				{ label: t('category'), onClick: function () {
					openPicker(t('chooseCategory'), typeItems, type, function (item) {
						state.knife.type = Number(item.weapon_defindex || 0);
						state.knife.paint = 0;
					}, function (item) {
						return item.image || weaponPlaceholder(item.weapon_name || 'weapon_knife');
					}, function (item) { return item.paint_name; }, function (item) { return item.weapon_defindex || 0; });
				} },
				{ label: t('skin'), disabled: type === 0, onClick: function () {
					openPicker(t('chooseSkin'), skins, state.knife.paint, function (item) {
						state.knife.paint = item.paint || 0;
					}, function (item) { return itemImage(item, weaponPlaceholder(item.weapon_name)); }, function (item) { return item.paint_name; }, function (item) { return item.paint || 0; });
				} },
				{ label: t('edit'), disabled: type === 0, onClick: function () { openEdit(current.paint_name, state.knife, { stattrak: true }); } }
			]
		}));
	}

	function renderGloves(grid) {
		var type = Number(state.gloves.type || 0);
		var skins = data.glovesByDef[String(type)] || [];
		var current = type > 0 ? byPaint(skins, state.gloves.paint) : { paint_name: t('useInventoryGloves'), image: '' };
		var typeItems = [{ weapon_defindex: 0, paint_name: t('useInventoryGloves'), image: 'img/skins/gloves.png' }].concat(data.gloveTypes);
		grid.appendChild(card(t('gloves'), current.paint_name, itemImage(current, gloveTypeImages[type] || 'img/skins/gloves.png'), {
			featured: true,
			extraClass: 'loadout-card',
			meta: { wear: state.gloves.wear || 0, seed: state.gloves.seed || 0 },
			actions: [
				{ label: t('category'), onClick: function () {
					openPicker(t('chooseCategory'), typeItems, type, function (item) {
						state.gloves.type = Number(item.weapon_defindex || 0);
						state.gloves.paint = 0;
					}, function (item) { return gloveTypeImages[item.weapon_defindex] || item.image || 'img/skins/gloves.png'; }, function (item) { return item.paint_name; }, function (item) { return item.weapon_defindex || 0; });
				} },
				{ label: t('skin'), disabled: type === 0, onClick: function () {
					openPicker(t('chooseSkin'), skins, state.gloves.paint, function (item) {
						state.gloves.paint = item.paint || 0;
					}, function (item) { return itemImage(item, gloveTypeImages[type] || 'img/skins/gloves.png'); }, function (item) { return item.paint_name; }, function (item) { return item.paint || 0; });
				} },
				{ label: t('edit'), disabled: type === 0, onClick: function () { openEdit(current.paint_name, state.gloves); } }
			]
		}));
	}

	function renderAgent(grid) {
		if (state.team !== 2 && state.team !== 3) return;
		var items = data.agents.filter(function (agent) { return Number(agent.team || 0) === state.team; });
		var current = items.find(function (agent) { return agent.model === state.agents[state.team]; }) || items[0] || { agent_name: t('useInventoryAgent'), image: '' };
		grid.appendChild(card(state.team === 2 ? t('tAgent') : t('ctAgent'), current.agent_name, itemImage(current, 'img/skins/agent.png'), {
			featured: true,
			actions: [{ label: t('select'), onClick: function () {
				openPicker(t('select'), items, current.model, function (item) {
					state.agents[state.team] = item.model || '';
				}, function (item) { return itemImage(item, 'img/skins/agent.png'); }, function (item) { return item.agent_name; }, function (item) { return item.model || ''; });
			} }]
		}));
	}

	function renderMusic(grid) {
		if (state.team !== 1) return;
		var items = [{ id: '0', name: t('useInventoryMusic'), image: 'img/skins/music_kit.png' }].concat(data.music);
		var current = items.find(function (item) { return String(item.id) === String(state.music); }) || items[0];
		grid.appendChild(card(t('music'), current.name, itemImage(current, 'img/skins/music_kit.png'), {
			featured: true,
			actions: [{ label: t('select'), onClick: function () {
				openPicker(t('select'), items, state.music, function (item) {
					state.music = item.id || '0';
				}, function (item) { return itemImage(item, 'img/skins/music_kit.png'); }, function (item) { return item.name; }, function (item) { return item.id; });
			} }]
		}));
	}

	function renderWeapon(grid, defindex) {
		var skins = byWeapon(defindex);
		if (!skins.length) return;
		var selected = state.weapons[defindex] || {};
		var currentPaint = selected.paint || 0;
		var current = byPaint(skins, currentPaint) || firstDefaultSkin(skins, '');
		var placeholder = weaponPlaceholder(current.weapon_name);
		grid.appendChild(card(current.weapon_name, current.paint_name, itemImage(current, placeholder), {
			extraClass: 'weapon-card',
			stattrak: !!selected.stattrak,
			stickers: selected.stickers || [],
			meta: { wear: selected.wear || 0, seed: selected.seed || 0 },
			actions: [
				{ label: t('skin'), onClick: function () {
					openPicker(t('chooseSkin'), skins, currentPaint, function (item) {
						state.weapons[defindex] = Object.assign({}, selected, { paint: item.paint || 0 });
					}, function (item) { return itemImage(item, placeholder); }, function (item) { return item.paint_name; }, function (item) { return item.paint || 0; });
				} },
				{ label: t('edit'), disabled: String(current.paint || 0) === '0', onClick: function () {
					if (!state.weapons[defindex]) state.weapons[defindex] = Object.assign({}, selected, { paint: current.paint || 0 });
					openEdit(current.paint_name, state.weapons[defindex], { stattrak: true, stickers: true, stickerSlots: stickerSlotCount(defindex) });
				} }
			]
		}));
	}

	function renderCards() {
		var grid = document.getElementById('cardGrid');
		grid.innerHTML = '';
		renderKnife(grid);
		renderGloves(grid);
		renderAgent(grid);
		renderMusic(grid);
		defaultWeapons.forEach(function (defindex) {
			renderWeapon(grid, defindex);
		});
	}

	function render() {
		renderHeader();
		renderCards();
	}

	function loadData() {
		return Promise.all([
			fetchJson(dataFile('skins')),
			fetchJson(dataFile('gloves')),
			fetchJson(dataFile('agents')),
			fetchJson(dataFile('music')),
			fetchJson(dataFile('stickers'))
		]).then(function (results) {
			data.skins = results[0];
			data.gloves = results[1];
			data.agents = results[2];
			data.music = results[3];
			data.stickers = results[4];
			data.skinsByDef = groupBy(data.skins, 'weapon_defindex');
			data.glovesByDef = groupBy(data.gloves, 'weapon_defindex');
			data.stickersById = data.stickers.reduce(function (map, item) {
				map[String(item.id)] = item;
				return map;
			}, {});
			data.knifeTypes = knifeDefindexes.map(function (defindex) {
				var item = firstDefaultSkin(byWeapon(defindex), 'weapon_knife');
				return Object.assign({}, item, { weapon_defindex: defindex });
			}).filter(function (item) { return item.paint_name; });
			data.gloveTypes = Object.keys(data.glovesByDef).map(function (defindex) {
				var items = data.glovesByDef[defindex] || [];
				var item = items[0] || firstDefaultSkin(items, t('useInventoryGloves'));
				return Object.assign({}, item, {
					weapon_defindex: Number(defindex),
					paint_name: categoryNameFromPaintName(item.paint_name || t('useInventoryGloves'))
				});
			}).filter(function (item) { return Number(item.weapon_defindex) > 0; });
		});
	}

	document.getElementById('identityForm').addEventListener('submit', function (event) {
		event.preventDefault();
		state.steamid = document.getElementById('steamid').value || '12345678';
		state.nickname = document.getElementById('nickname').value || 'Example';
		saveState();
		renderHeader();
	});

	document.getElementById('backLink').addEventListener('click', function (event) {
		event.preventDefault();
	});

	document.getElementById('pickerClose').addEventListener('click', closePicker);
	document.getElementById('pickerModal').addEventListener('click', function (event) {
		if (event.target.id === 'pickerModal') closePicker();
	});
	document.getElementById('editClose').addEventListener('click', closeEdit);
	document.getElementById('editCancel').addEventListener('click', closeEdit);
	document.getElementById('editModal').addEventListener('click', function (event) {
		if (event.target.id === 'editModal') closeEdit();
	});

	document.querySelectorAll('[data-language]').forEach(function (link) {
		link.addEventListener('click', function (event) {
			event.preventDefault();
			state.language = link.dataset.language;
			saveState();
			loadData().then(render);
		});
	});

	loadData().then(render).catch(function (error) {
		document.getElementById('cardGrid').innerHTML = '<section class="panel">Failed to load preview data: ' + error.message + '</section>';
	});
})();
JS;

writeFile($target . '/index.html', $indexHtml);
writeFile($target . '/preview.js', $previewJs);

echo "Preview website generated at: {$target}" . PHP_EOL;
