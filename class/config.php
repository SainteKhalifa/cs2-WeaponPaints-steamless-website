<?php
define('DEFAULT_LANGUAGE', 'en'); // Available values: en, zh-CN
define('SITE_NAME_EN', 'CS2 WeaponPaints Loadout Manager'); // English name and fallback
define('SITE_NAME_ZH_CN', 'CS2 WeaponPaints 配置管理器'); // Simplified Chinese name
define('SITE_ACCESS_PASSWORD', ''); // Set a password to enable access protection
define('ADMIN_PASSWORD', ''); // Leave empty to disable administrator mode
define('AUTH_RATE_LIMIT_ATTEMPTS', 5); // Failed attempts allowed within the time window
define('AUTH_RATE_LIMIT_WINDOW_SECONDS', 1800); // Failure tracking window: 30 minutes
define('AUTH_RATE_LIMIT_LOCK_SECONDS', 60); // Lock duration: 1 minute
define('ENABLE_SKIN_FUSION', true); // Allow cross-weapon paint combinations

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

define('WEB_STYLE_DARK', true);
