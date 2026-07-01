<p align="center">
    <a href="README.md">
        <img src="https://img.shields.io/badge/LANG-English-blue">
    </a>
    <a href="README_cn.md">
        <img src="https://img.shields.io/badge/LANG-Simplified%20Chinese-red">
    </a>
</p>

# CS2 WeaponPaints Preset Manager

> A WeaponPaints skin preset management panel for private CS2 community servers.
> No Steam login is required. Enter a Steam64 ID and write the corresponding settings to the database.

## Why This Exists

The original WeaponPaints website identifies players through Steam login, then writes their selected skin settings to the database used by WeaponPaints.

Logging into Steam can sometimes raise security concerns or create network access issues, so this project provides a website where players can enter their Steam64 ID manually.

Because Steam login has been removed, this project is better suited for private CS2 community servers and small trusted player groups. Players can create or select presets directly by entering a Steam64 ID.

Compared with the original website, this project adds a preset system, website access password, name tags, StatTrak™, sticker editing, music kit selection, agent selection, image fallback loading, and a CS2 skin data updater.

**This project does not require Steam login.**

This also means it is not an account security system for public websites. Anyone who can access the website can edit presets by entering a Steam64 ID. HTTPS and a website access password are recommended.

## Interface

![Preview 1](./img/preview/1.png)
![Preview 2](./img/preview/2.png)
![Preview 3](./img/preview/3.png)
![Preview 4](./img/preview/4.png)

## Features

### Preset Management

* Manage presets by Steam64 ID without Steam login
* Add optional nicknames to presets for easier player identification
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

This website is rewritten based on the database workflow and usage pattern of the original WeaponPaints web panel. It removes Steam login for private-server use and adds preset management, more cosmetic editing features, language switching, and data updating.

Thanks to:

* [Nereziel/cs2-WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints): for the CS2 WeaponPaints plugin and original web workflow.
* [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API): for the CS2 cosmetic data used by this project's updater.

## Supported Languages

The website currently supports:

* English (`en`)
* Simplified Chinese (`zh-CN`)

Both the UI and cosmetic item data support language switching. Item names and related CS2 data come from [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API).

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

4. If `SITE_ACCESS_PASSWORD` is set, enter the access password on first visit.

5. Create a preset with a Steam64 ID and an optional nickname.

6. Select and edit skin settings as needed.

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

* `wp_presets`: stores the website preset list and nicknames
* `wp_skin_settings_cache`: stores website-side per-skin settings such as wear, pattern template, StatTrak™, and name tags, so these settings can be remembered when switching skins

The website reads `wp_presets` first, then reads and writes WeaponPaints data according to the selected Steam64 ID.

## Skin Setting Modes

- Global mode: applies to both T and CT
- T-side mode: edits only T-side settings
- CT-side mode: edits only CT-side settings

## Saving

- Dropdown selections are saved immediately
- Detailed settings are saved only after clicking the Save button in the dialog, such as wear, StatTrak™, name tags, and stickers

## Notes

* Sticker editing applies only to weapon skins. Most weapons have 4 default sticker slots, while weapons with 5 default sticker slots will show 5 slots.
* The website displays local placeholder images first, then automatically replaces them after remote images load successfully.
* Cosmetic data is stored in the `data/` directory and maintained through `tools/update_cs2_data.php`.
* Keychains and collectibles data are already prepared for future feature expansion.

## Security Notes

This project is intended for private or trusted server environments.
