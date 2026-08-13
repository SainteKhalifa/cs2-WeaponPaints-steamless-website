<?php
class UtilsClass
{
    private static ?string $languageOverride = null;

    public static function setLanguage(string $language): void
    {
        self::$languageOverride = $language;
    }

    public static function currentLanguage(): string
    {
        return self::$languageOverride ?? (defined('DEFAULT_LANGUAGE') ? DEFAULT_LANGUAGE : 'zh-CN');
    }

    public static function languageFile(string $prefix, string $configuredLanguage): string
    {
        $currentLanguage = self::currentLanguage();
        if (in_array($currentLanguage, ['zh-CN', 'en'], true)) {
            return "{$prefix}_{$currentLanguage}";
        }
        return $configuredLanguage;
    }

    public static function dataFromJson(string $language, string $fallbackLanguage): array
    {
        $files = array_values(array_unique([$language, $fallbackLanguage]));
        foreach ($files as $file) {
            $path = __DIR__ . "/../data/{$file}.json";
            if (!is_file($path)) {
                error_log("CS2 data file not found: {$path}");
                continue;
            }

			$raw = file_get_contents($path);
			if ($raw === false) {
				error_log("Unable to read CS2 data file: {$path}");
				continue;
			}
			$json = json_decode($raw, true);
			if (!is_array($json)) {
				error_log("Invalid CS2 data JSON in {$path}: " . json_last_error_msg());
				continue;
			}
			return $json;
		}

		return [];
    }


    private static function weaponSortMap(): array
    {
        $path = __DIR__ . '/weapon_order.php';
        $order = is_file($path) ? require $path : [];
        if (!is_array($order)) {
            return [];
        }

        $order = array_values(array_filter(array_map('intval', $order), static fn($defindex) => $defindex > 0));
        return array_flip($order);
    }

    private static function sortSkinsByWeaponOrder(array $skins): array
    {
        $order = self::weaponSortMap();
        foreach ($skins as &$paints) {
            ksort($paints, SORT_NUMERIC);
        }
        unset($paints);

        uksort($skins, static function ($a, $b) use ($order): int {
            $aRank = $order[(int)$a] ?? (10000 + (int)$a);
            $bRank = $order[(int)$b] ?? (10000 + (int)$b);
            return $aRank <=> $bRank;
        });

        return $skins;
    }

    public static function skinsFromJson(): array
    {
        $skins = [];
		$language = self::languageFile('skins', defined('SKIN_LANGUAGE') ? SKIN_LANGUAGE : 'skins_en');
		$json = self::dataFromJson($language, $language === 'skins_en' ? 'skins_zh-CN' : 'skins_en');

        foreach ($json as $skin) {
            $skins[(int) $skin['weapon_defindex']][(int) $skin['paint']] = [
                'weapon_name' => $skin['weapon_name'],
                'paint_name' => $skin['paint_name'],
                'image_url' => $skin['image'],
            ];
        }

        return self::sortSkinsByWeaponOrder($skins);
    }

    public static function paintKitsFromJson(): array
    {
		$language = self::languageFile('paint_kits', 'paint_kits_en');
		return self::dataFromJson($language, $language === 'paint_kits_en' ? 'paint_kits_zh-CN' : 'paint_kits_en');
    }

    public static function agentsFromJson(): array
    {
		$language = self::languageFile('agents', defined('AGENT_LANGUAGE') ? AGENT_LANGUAGE : 'agents_en');
		return self::dataFromJson($language, $language === 'agents_en' ? 'agents_zh-CN' : 'agents_en');
    }

    public static function glovesFromJson(): array
    {
		$language = self::languageFile('gloves', defined('GLOVE_LANGUAGE') ? GLOVE_LANGUAGE : 'gloves_en');
		return self::dataFromJson($language, $language === 'gloves_en' ? 'gloves_zh-CN' : 'gloves_en');
    }

    public static function keychainsFromJson(): array
    {
		$language = self::languageFile('keychains', defined('KEYCHAIN_LANGUAGE') ? KEYCHAIN_LANGUAGE : 'keychains_en');
		return self::dataFromJson($language, $language === 'keychains_en' ? 'keychains_zh-CN' : 'keychains_en');
    }

    public static function pinsFromJson(): array
    {
		$language = self::languageFile('collectibles', defined('PIN_LANGUAGE') ? PIN_LANGUAGE : 'collectibles_en');
		return self::dataFromJson($language, $language === 'collectibles_en' ? 'collectibles_zh-CN' : 'collectibles_en');
    }

    public static function musicFromJson(): array
    {
		$language = self::languageFile('music', defined('MUSIC_LANGUAGE') ? MUSIC_LANGUAGE : 'music_en');
		return self::dataFromJson($language, $language === 'music_en' ? 'music_zh-CN' : 'music_en');
    }

    public static function stickersFromJson(): array
    {
		$language = self::languageFile('stickers', defined('STICKER_LANGUAGE') ? STICKER_LANGUAGE : 'stickers_en');
		return self::dataFromJson($language, $language === 'stickers_en' ? 'stickers_zh-CN' : 'stickers_en');
    }

    public static function getWeaponsFromArray()
    {
        $weapons = [];
        $temp = self::skinsFromJson();

        foreach ($temp as $key => $value) {
            if (key_exists($key, $weapons))
                continue;

            $defaultSkin = $value[0] ?? reset($value);
            if (!is_array($defaultSkin)) {
                error_log("Skipping weapon defindex {$key}: no usable skin data.");
                continue;
            }
            if (!isset($value[0])) {
                error_log("Weapon defindex {$key} is missing paint 0; using the first available row as a display fallback.");
            }

            $weapons[$key] = [
                'weapon_name' => (string)($defaultSkin['weapon_name'] ?? "weapon_{$key}"),
                'paint_name' => (string)($defaultSkin['paint_name'] ?? ''),
                'image_url' => (string)($defaultSkin['image_url'] ?? ''),
            ];
        }

        return $weapons;
    }

    public static function getKnifeTypes()
    {
        $knifes = [];
        $temp = self::getWeaponsFromArray();

        foreach ($temp as $key => $weapon) {
            if (
                !in_array($key, [
                    500,
                    503,
                    505,
                    506,
                    507,
                    508,
                    509,
                    512,
                    514,
                    515,
                    516,
                    517,
                    518,
                    519,
                    520,
                    521,
                    522,
                    523,
                    525,
                    526
                ])
            )
                continue;

            $knifes[$key] = [
                'weapon_name' => $weapon['weapon_name'],
                'paint_name' => preg_replace('/^(使用库存|Use inventory)\s+/u', '', rtrim(explode("|", $weapon['paint_name'])[0])),
                'image_url' => $weapon['image_url'],
            ];
            $knifes[0] = [
                'weapon_name' => "weapon_knife",
                'paint_name' => self::currentLanguage() === 'en' ? 'Use inventory knife' : '使用库存匕首',
                'image_url' => "img/skins/knife.png",
            ];
        }

        ksort($knifes);
        return $knifes;
    }

    public static function getSelectedSkins(array $temp)
    {
        $selected = [];

        foreach ($temp as $weapon) {
            $selected[$weapon['weapon_defindex']] =  [
                'weapon_paint_id' => $weapon['weapon_paint_id'],
                'weapon_seed' => $weapon['weapon_seed'],
                'weapon_wear' => $weapon['weapon_wear'],
                'weapon_stattrak' => $weapon['weapon_stattrak'] ?? 0,
                'weapon_stattrak_count' => $weapon['weapon_stattrak_count'] ?? 0,
                'weapon_nametag' => $weapon['weapon_nametag'] ?? null,
                'weapon_sticker_0' => $weapon['weapon_sticker_0'] ?? '0;0;0;0;0;0;0',
                'weapon_sticker_1' => $weapon['weapon_sticker_1'] ?? '0;0;0;0;0;0;0',
                'weapon_sticker_2' => $weapon['weapon_sticker_2'] ?? '0;0;0;0;0;0;0',
                'weapon_sticker_3' => $weapon['weapon_sticker_3'] ?? '0;0;0;0;0;0;0',
                'weapon_sticker_4' => $weapon['weapon_sticker_4'] ?? '0;0;0;0;0;0;0',
                'weapon_keychain' => $weapon['weapon_keychain'] ?? '0;0;0;0;0',
            ];
        }

        return $selected;
    }
}
