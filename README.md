<p align="center">
    <a href="README.md">
        <img src="https://img.shields.io/badge/LANG-English-blue">
    </a>
    <a href="README_cn.md">
        <img src="https://img.shields.io/badge/语言-简体中文-red">
    </a>
</p>

# CS2 WeaponPaints Loadout Manager

> A WeaponPaints skin loadout management panel for private CS2 community servers.
> No Steam login is required. Enter a Steam64 ID and write the corresponding settings to the database.

## Why This Exists

The original WeaponPaints website identifies players through Steam login, then writes their selected skin settings to the database used by WeaponPaints.

Logging into Steam can sometimes raise security concerns or create network access issues, so this project provides a website where players can enter their Steam64 ID manually.

Because Steam login has been removed, this project is better suited for private CS2 community servers and small trusted player groups. Players can create or select loadouts directly by entering a Steam64 ID.

Compared with the original website, this project adds a loadout system, website access password, per-loadout edit PINs, administrator mode, name tags, StatTrak™, sticker editing, music kit selection, agent selection, image fallback loading, and a CS2 skin data updater.

**This project does not require Steam login.**

This is still not a full account system for public websites. Without an edit PIN, anyone who can access the website can edit a loadout by entering its Steam64 ID. HTTPS, a website access password, and per-loadout PINs are recommended.

## Interface

<p align="center">
    <img src="./preview/img/1.png" width="45%">
    <img src="./preview/img/2.png" width="45%">
</p>

<p align="center">
    <img src="./preview/img/3.png" width="45%">
    <img src="./preview/img/4.png" width="45%">
</p>

## Features

### Loadout Management

* Manage loadouts by Steam64 ID without Steam login
* Add optional nicknames to loadouts for easier player identification
* Protect individual loadouts with optional hashed edit PINs
* Administrator mode can manage every loadout and reset its PIN
* Global, T-side, and CT-side editing modes

### Skin Editing

* Supports weapons, knives, gloves, agents, and music kits
* Supports wear, pattern template, StatTrak™, and name tags
* Supports weapon sticker selection with one-click fill and one-click clear

### Website and Data

* Supports website access password protection
* Works on desktop and mobile
* Supports English and Simplified Chinese UI
* Uses local placeholder images first, then replaces them when remote images load successfully
* Uses images from `steamstatic.com` so they are accessible in most regions
* Provides a CS2 skin data updater powered by [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API)

## Credits

This website is rewritten based on the database workflow and usage pattern of the original WeaponPaints web panel. It removes Steam login for private-server use and adds loadout management, more skin editing features, language switching, and data updating.

Thanks to:

* [Nereziel/cs2-WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints): for the CS2 WeaponPaints plugin and original web workflow.
* [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API): for the CS2 skin data used by this project's updater.

## Supported Languages

The website currently supports:

* English (`en`)
* Simplified Chinese (`zh-CN`)

Both the UI and skin data support language switching. Item names and related CS2 data come from [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API).

The data source currently provides English and Simplified Chinese data. More languages can be added later if the data source or this project adds support for them.

## Requirements

- A PHP-capable web server
- An existing database
- A working CS2 community server with [WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints) already connected to the same database

## Quick Start

1. Copy this project folder to your web server directory.

   XAMPP example:

   ```text
   ...\xampp\htdocs\cs2-WeaponPaints-steamless-website
   ```

2. Edit `class/config.php` and configure the default language and database connection.

   ```php
   <?php
   define('DEFAULT_LANGUAGE', 'en'); // Available values: en, zh-CN
   define('SITE_ACCESS_PASSWORD', ''); // Set a password to enable access protection
   define('ADMIN_PASSWORD', ''); // Leave empty to disable administrator mode
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

3. Visit the website.

   ```text
   http://your-server/your-folder/
   ```

4. If `SITE_ACCESS_PASSWORD` is set, enter the access password on first visit. Set `ADMIN_PASSWORD` to enable the administrator button; leave it empty to keep administrator mode disabled.

5. Create a loadout with a Steam64 ID, an optional nickname, and an optional edit PIN.

6. Select and edit skin settings as needed.

## Loadout PINs and Administrator Mode

This feature is not a user registration system. A PIN protects one loadout from casual edits while keeping the Steamless workflow.

### Loadout PINs

* A PIN can be enabled while creating a loadout or later from the Basic Information section of its edit page.
* Protected loadouts display a lock and `PIN` badge on the loadout list.
* Opening a protected loadout requires its PIN. After successful verification, that loadout remains unlocked for the current PHP browser session.
* Entering a new PIN in Basic Information replaces the existing PIN. Leaving the field empty keeps the current PIN unchanged.
* Turn off **Enable PIN** and save Basic Information to remove the PIN.
* Loadouts without a PIN continue to open and save normally.

### Administrator Mode

Set `ADMIN_PASSWORD` in `class/config.php` to enable administrator mode. The administrator button appears next to the language button. Leave `ADMIN_PASSWORD` empty to keep this feature disabled.

After signing in, an administrator can open every loadout without its PIN, edit Steam64 IDs and nicknames, and set, replace, or remove any loadout PIN. Administrator access lasts for the current PHP session and can be ended from the same administrator dialog.

The website access password remains the first protection layer. Loadout PINs and administrator mode do not replace `SITE_ACCESS_PASSWORD`.

## Updating CS2 Data

Run the command-line updater:

```bash
php tools/update_cs2_data.php
```

Preview changes without writing files:

```bash
php tools/update_cs2_data.php --dry-run
```

Update only skins and gloves:

```bash
php tools/update_cs2_data.php --only=skins
```

Remote source JSON files are cached in:

```text
data/.source_cache/
```

If a cached source file exists and contains valid JSON, the updater uses it before requesting GitHub raw again. Delete the matching cache file when you want to force a fresh download from the upstream source.

The updater creates backups in:

```text
data/backups/
```

## Weapon Order

The website and updater share this file to decide the weapon card order:

```text
class/weapon_order.php
```

Edit this file if you want to change the order of weapon cards on the page.

Knife-related weapons may still appear in `weapon_order.php` for data sorting, but the website displays them through the dedicated knife card.

## Database Tables

This project reads and writes the following WeaponPaints tables:

* `wp_player_skins`
* `wp_player_knife`
* `wp_player_gloves`
* `wp_player_agents`
* `wp_player_music`

In addition, the website automatically creates two helper tables for its own use. If the configured database user has the required permissions, they will be created when the website is visited:

* `wp_presets`: stores the website loadout list, nicknames, and optional password-hashed edit PINs
* `wp_skin_settings_cache`: stores website-side per-skin settings such as wear, pattern template, StatTrak™, and name tags, so these settings can be remembered when switching skins

The website reads `wp_presets` first, then reads and writes WeaponPaints data according to the selected Steam64 ID.

The website automatically adds `edit_pin_hash VARCHAR(255) NULL` to an existing `wp_presets` table. No manual SQL is required when the configured database user has `ALTER` permission. Otherwise, run:

```sql
ALTER TABLE `wp_presets` ADD `edit_pin_hash` VARCHAR(255) NULL AFTER `nickname`;
```

## Notes

* Sticker editing applies only to weapon skins. Most weapons have 4 default sticker slots, while weapons with 5 default sticker slots will show 5 slots.
* The website displays local placeholder images first, then automatically replaces them after remote images load successfully.
* Skin data is stored in the `data/` directory and maintained through `tools/update_cs2_data.php`.
* Keychains and collectibles data are already prepared for future feature expansion.

## Security Notes

This project is intended for private or trusted server environments.

Loadout PINs are stored with PHP `password_hash()` and verified with `password_verify()`; the original PIN cannot be read back from the database. The administrator password remains a server-side `class/config.php` setting and is never included in rendered page data. HTTPS is strongly recommended whenever website passwords, PINs, or administrator mode are enabled.
