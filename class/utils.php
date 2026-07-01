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
        $path = __DIR__ . "/../data/{$language}.json";
        if (!is_file($path)) {
            $path = __DIR__ . "/../data/{$fallbackLanguage}.json";
        }

        $json = json_decode(file_get_contents($path), true);
        return is_array($json) ? $json : [];
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
        $json = self::dataFromJson(self::languageFile('skins', defined('SKIN_LANGUAGE') ? SKIN_LANGUAGE : 'skins_en'), 'skins_en');

        foreach ($json as $skin) {
            $skins[(int) $skin['weapon_defindex']][(int) $skin['paint']] = [
                'weapon_name' => $skin['weapon_name'],
                'paint_name' => $skin['paint_name'],
                'image_url' => $skin['image'],
            ];
        }

        return self::sortSkinsByWeaponOrder($skins);
    }

    public static function agentsFromJson(): array
    {
        return self::dataFromJson(self::languageFile('agents', defined('AGENT_LANGUAGE') ? AGENT_LANGUAGE : 'agents_en'), 'agents_en');
    }

    public static function glovesFromJson(): array
    {
        return self::dataFromJson(self::languageFile('gloves', defined('GLOVE_LANGUAGE') ? GLOVE_LANGUAGE : 'gloves_en'), 'gloves_en');
    }

    public static function keychainsFromJson(): array
    {
        return self::dataFromJson(self::languageFile('keychains', defined('KEYCHAIN_LANGUAGE') ? KEYCHAIN_LANGUAGE : 'keychains_en'), 'keychains_en');
    }

    public static function musicFromJson(): array
    {
        return self::dataFromJson(self::languageFile('music', defined('MUSIC_LANGUAGE') ? MUSIC_LANGUAGE : 'music_en'), 'music_en');
    }

    public static function stickersFromJson(): array
    {
        return self::dataFromJson(self::languageFile('stickers', defined('STICKER_LANGUAGE') ? STICKER_LANGUAGE : 'stickers_en'), 'stickers_en');
    }

    public static function getWeaponsFromArray()
    {
        $weapons = [];
        $temp = self::skinsFromJson();

        foreach ($temp as $key => $value) {
            if (key_exists($key, $weapons))
                continue;

            $weapons[$key] = [
                'weapon_name' => $value[0]['weapon_name'],
                'paint_name' => $value[0]['paint_name'],
                'image_url' => $value[0]['image_url'],
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
                'weapon_nametag' => $weapon['weapon_nametag'] ?? null,
                'weapon_sticker_0' => $weapon['weapon_sticker_0'] ?? '0;0;0;0;0;0;0',
                'weapon_sticker_1' => $weapon['weapon_sticker_1'] ?? '0;0;0;0;0;0;0',
                'weapon_sticker_2' => $weapon['weapon_sticker_2'] ?? '0;0;0;0;0;0;0',
                'weapon_sticker_3' => $weapon['weapon_sticker_3'] ?? '0;0;0;0;0;0;0',
                'weapon_sticker_4' => $weapon['weapon_sticker_4'] ?? '0;0;0;0;0;0;0',
            ];
        }

        return $selected;
    }
}
