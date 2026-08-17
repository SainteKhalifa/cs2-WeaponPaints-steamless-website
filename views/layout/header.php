<!DOCTYPE html>
<?php
// The accent colour follows the side being edited. Anywhere else, including the
// loadout list, there is no side to reflect and the default accent stands.
$themeTeam = $action === 'edit' && isset($team) ? (int)$team : 0;
?>
<html lang="<?= h($currentLanguage) ?>" data-bs-theme="<?= h($defaultWebTheme) ?>"<?= $themeTeam ? ' data-team="' . $themeTeam . '"' : '' ?>>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>
		(function () {
			var theme = document.documentElement.dataset.bsTheme;
			try {
				var savedTheme = window.localStorage.getItem('cs2_wp_theme');
				if (savedTheme === 'dark' || savedTheme === 'light') theme = savedTheme;
			} catch (error) {
				// Browsers can block localStorage in restricted privacy modes.
			}
			document.documentElement.dataset.bsTheme = theme === 'light' ? 'light' : 'dark';
		})();
	</script>
	<link href="assets/bootstrap/bootstrap.min.css?v=<?= filemtime(__DIR__ . '/../../assets/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
	<script src="assets/bootstrap/bootstrap.bundle.min.js?v=<?= filemtime(__DIR__ . '/../../assets/bootstrap/bootstrap.bundle.min.js') ?>"></script>
	<link rel="icon" type="image/png" href="assets/icons/favicon.png?v=<?= filemtime(__DIR__ . '/../../assets/icons/favicon.png') ?>">
	<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
	<title><?= h($siteName) ?></title>
</head>
<body>
	<main class="app-shell<?= $action === 'access' ? ' access-shell' : '' ?>">

