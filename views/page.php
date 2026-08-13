<?php

$pageFiles = [
	'access' => __DIR__ . '/pages/access.php',
	'home' => __DIR__ . '/pages/home.php',
	'new' => __DIR__ . '/pages/new.php',
	'list' => __DIR__ . '/pages/list.php',
	'edit' => __DIR__ . '/pages/edit.php',
];
require $pageFiles[$action] ?? $pageFiles['home'];

require __DIR__ . '/components/shared-modals.php';
require __DIR__ . '/components/page-controls.php';
