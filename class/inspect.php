<?php

/**
 * Encodage et décodage des liens d'inspection CS2 (CEconItemPreviewDataBlock).
 *
 * Sert de pont entre une ligne `wp_player_skins` et un visualiseur 3D externe :
 * on encode le loadout en lien d'inspection, le joueur place ses stickers et son
 * charm dans le visualiseur, puis on redécode le lien qu'il rapporte.
 *
 * Le protobuf est encodé et décodé à la main : aucune dépendance externe.
 */
class InspectLink
{
    public const VIEWER_URL = 'https://skincraft.gg/i/';
    public const VIEWER_EMBED_URL = 'https://skincraft.gg/embed/inspect/';

    /** Champs de CEconItemPreviewDataBlock. */
    private const F_DEFINDEX = 3;
    private const F_PAINTINDEX = 4;
    private const F_QUALITY = 6;
    private const F_PAINTWEAR = 7;
    private const F_PAINTSEED = 8;
    private const F_KILLEATERSCORETYPE = 9;
    private const F_KILLEATERVALUE = 10;
    private const F_CUSTOMNAME = 11;
    private const F_STICKERS = 12;
    private const F_KEYCHAINS = 20;

    /** Champs du sous-message Sticker (réutilisé pour les charms). */
    private const S_SLOT = 1;
    private const S_STICKER_ID = 2;
    private const S_WEAR = 3;
    private const S_SCALE = 4;
    private const S_ROTATION = 5;
    private const S_OFFSET_X = 7;
    private const S_OFFSET_Y = 8;
    private const S_OFFSET_Z = 9;
    private const S_PATTERN = 10;

    private const WIRE_VARINT = 0;
    private const WIRE_FIXED64 = 1;
    private const WIRE_BYTES = 2;
    private const WIRE_FIXED32 = 5;

    /** Quality 9 = Strange, la représentation canonique du StatTrak. */
    private const QUALITY_STRANGE = 9;

    /** Longueur minimale plausible pour l'enveloppe hexadécimale d'un lien. */
    private const MIN_HEX_LENGTH = 32;

    public static function viewerUrl(string $hex): string
    {
        return self::VIEWER_URL . strtoupper($hex);
    }

    public static function embedUrl(string $hex): string
    {
        return self::VIEWER_EMBED_URL . strtoupper($hex);
    }

    /**
     * Construit un item normalisé à partir d'une ligne `wp_player_skins`.
     *
     * @param array $row      Ligne de base (weapon_defindex, weapon_paint_id, ...).
     * @param array $stickers Liste de slots ['id','x','y','wear','scale','rotation'].
     * @param array|null $keychain ['id','x','y','z','seed'] ou null.
     */
    public static function itemFromParts(array $row, array $stickers, ?array $keychain): array
    {
        $normalized = [];
        foreach ($stickers as $slot => $sticker) {
            if ((int)($sticker['id'] ?? 0) <= 0) {
                continue;
            }
            $normalized[] = [
                'slot' => (int)$slot,
                'id' => (int)$sticker['id'],
                'x' => (float)($sticker['x'] ?? 0),
                'y' => (float)($sticker['y'] ?? 0),
                'wear' => (float)($sticker['wear'] ?? 0),
                'scale' => (float)($sticker['scale'] ?? 1),
                'rotation' => (float)($sticker['rotation'] ?? 0),
            ];
        }

        if ($keychain !== null && (int)($keychain['id'] ?? 0) <= 0) {
            $keychain = null;
        }

        return [
            'defindex' => (int)($row['weapon_defindex'] ?? 0),
            'paintindex' => (int)($row['weapon_paint_id'] ?? 0),
            'paintwear' => (float)($row['weapon_wear'] ?? 0),
            'paintseed' => (int)($row['weapon_seed'] ?? 0),
            'stattrak' => (int)($row['weapon_stattrak'] ?? 0) === 1,
            'stattrak_count' => (int)($row['weapon_stattrak_count'] ?? 0),
            'customname' => (string)($row['weapon_nametag'] ?? ''),
            'stickers' => $normalized,
            'keychain' => $keychain,
        ];
    }

    /**
     * Encode un item normalisé en enveloppe hexadécimale.
     */
    public static function encode(array $item): string
    {
        $body = '';
        $body .= self::putVarint(self::F_DEFINDEX, max(0, (int)($item['defindex'] ?? 0)));
        $body .= self::putVarint(self::F_PAINTINDEX, max(0, (int)($item['paintindex'] ?? 0)));

        if (!empty($item['stattrak'])) {
            $body .= self::putVarint(self::F_QUALITY, self::QUALITY_STRANGE);
        }

        // paintwear est un uint32 qui transporte les bits du float, pas un entier.
        $body .= self::putVarint(self::F_PAINTWEAR, self::floatToBits((float)($item['paintwear'] ?? 0)));
        $body .= self::putVarint(self::F_PAINTSEED, max(0, (int)($item['paintseed'] ?? 0)));

        if (!empty($item['stattrak'])) {
            // Le compteur ne s'affiche que si killeaterscoretype est présent, même à 0.
            $body .= self::putVarint(self::F_KILLEATERSCORETYPE, 0);
            $body .= self::putVarint(self::F_KILLEATERVALUE, max(0, (int)($item['stattrak_count'] ?? 0)));
        }

        $customName = (string)($item['customname'] ?? '');
        if ($customName !== '') {
            $body .= self::putBytes(self::F_CUSTOMNAME, $customName);
        }

        foreach ($item['stickers'] ?? [] as $sticker) {
            if ((int)($sticker['id'] ?? 0) <= 0) {
                continue;
            }
            $body .= self::putBytes(self::F_STICKERS, self::encodeSticker($sticker));
        }

        $keychain = $item['keychain'] ?? null;
        if (is_array($keychain) && (int)($keychain['id'] ?? 0) > 0) {
            $body .= self::putBytes(self::F_KEYCHAINS, self::encodeKeychain($keychain));
        }

        return self::wrap($body);
    }

    /**
     * Décode un lien d'inspection en item normalisé.
     *
     * Accepte l'URL complète du visualiseur, un lien `steam://` ou le hex brut.
     *
     * @return array{ok:bool,error:?string,item:?array}
     */
    public static function decode(string $input): array
    {
        $hex = self::extractHex($input);
        if ($hex === null) {
            return self::failure('invalid');
        }

        $raw = @hex2bin($hex);
        if ($raw === false || strlen($raw) < 6) {
            return self::failure('invalid');
        }

        // Certains liens sont masqués : le premier octet sert de clé de XOR.
        $mask = ord($raw[0]);
        if ($mask !== 0) {
            $length = strlen($raw);
            for ($i = 0; $i < $length; $i++) {
                $raw[$i] = chr(ord($raw[$i]) ^ $mask);
            }
        }

        $body = substr($raw, 1, -4);
        if ($body === false || $body === '') {
            return self::failure('invalid');
        }

        $fields = self::parse($body);
        if ($fields === null || !isset($fields[self::F_DEFINDEX])) {
            return self::failure('invalid');
        }

        $stickers = [];
        foreach ($fields[self::F_STICKERS] ?? [] as $entry) {
            if ($entry['wire'] !== self::WIRE_BYTES) {
                continue;
            }
            $sticker = self::decodeSticker($entry['value']);
            if ($sticker !== null) {
                $stickers[] = $sticker;
            }
        }

        $keychain = null;
        foreach ($fields[self::F_KEYCHAINS] ?? [] as $entry) {
            if ($entry['wire'] !== self::WIRE_BYTES) {
                continue;
            }
            $keychain = self::decodeKeychain($entry['value']);
            if ($keychain !== null) {
                break;
            }
        }

        $scoreType = self::readInt($fields, self::F_KILLEATERSCORETYPE);
        $quality = self::readInt($fields, self::F_QUALITY);

        return [
            'ok' => true,
            'error' => null,
            'item' => [
                'defindex' => (int)(self::readInt($fields, self::F_DEFINDEX) ?? 0),
                'paintindex' => (int)(self::readInt($fields, self::F_PAINTINDEX) ?? 0),
                'paintwear' => self::readFloat($fields, self::F_PAINTWEAR) ?? 0.0,
                'paintseed' => (int)(self::readInt($fields, self::F_PAINTSEED) ?? 0),
                'stattrak' => $scoreType !== null || $quality === self::QUALITY_STRANGE,
                'stattrak_count' => (int)(self::readInt($fields, self::F_KILLEATERVALUE) ?? 0),
                'customname' => self::readString($fields, self::F_CUSTOMNAME) ?? '',
                'stickers' => $stickers,
                'keychain' => $keychain,
            ],
        ];
    }

    /**
     * Valide un item décodé contre les données de référence du site.
     *
     * Un joueur peut forger un lien arbitraire : rien de ce qui sort du décodeur
     * ne doit atteindre la base sans être recoupé ici.
     *
     * @param array $reference [
     *     'defindex' => int,   arme en cours d'édition
     *     'paints'   => array, paint_id autorisés pour cette arme
     *     'stickers' => array, ids de stickers connus
     *     'keychains'=> array, ids de charms connus
     *     'slots'    => int,   nombre de slots stickers de l'arme
     * ]
     * @return array{ok:bool,error:?string,item:?array}
     */
    public static function sanitize(array $item, array $reference): array
    {
        $expectedDefindex = (int)($reference['defindex'] ?? 0);
        if ($expectedDefindex > 0 && (int)($item['defindex'] ?? 0) !== $expectedDefindex) {
            return self::failure('weapon_mismatch');
        }

        $paints = $reference['paints'] ?? [];
        $paintIndex = (int)($item['paintindex'] ?? 0);
        if ($paints && !array_key_exists($paintIndex, $paints)) {
            return self::failure('unknown_paint');
        }

        $slotCount = max(0, min(5, (int)($reference['slots'] ?? 5)));
        $knownStickers = $reference['stickers'] ?? [];
        $stickers = [];
        foreach ($item['stickers'] ?? [] as $sticker) {
            $slot = (int)($sticker['slot'] ?? 0);
            $id = (int)($sticker['id'] ?? 0);
            if ($slot < 0 || $slot >= $slotCount || $id <= 0) {
                continue;
            }
            if ($knownStickers && !array_key_exists($id, $knownStickers)) {
                continue;
            }
            $stickers[$slot] = [
                'slot' => $slot,
                'id' => $id,
                'x' => self::clamp($sticker['x'] ?? 0, -1, 1, 0),
                'y' => self::clamp($sticker['y'] ?? 0, -1, 1, 0),
                'wear' => self::clamp($sticker['wear'] ?? 0, 0, 1, 0),
                'scale' => self::clamp($sticker['scale'] ?? 1, 0.2, 5, 1),
                'rotation' => self::normalizeRotation($sticker['rotation'] ?? 0),
            ];
        }
        ksort($stickers);

        $keychain = null;
        $rawKeychain = $item['keychain'] ?? null;
        if (is_array($rawKeychain)) {
            $id = (int)($rawKeychain['id'] ?? 0);
            $knownKeychains = $reference['keychains'] ?? [];
            if ($id > 0 && (!$knownKeychains || array_key_exists($id, $knownKeychains))) {
                $keychain = [
                    'id' => $id,
                    'x' => self::clamp($rawKeychain['x'] ?? 0, -100, 100, 0),
                    'y' => self::clamp($rawKeychain['y'] ?? 0, -100, 100, 0),
                    'z' => self::clamp($rawKeychain['z'] ?? 0, -100, 100, 0),
                    'seed' => max(0, min(100000, (int)($rawKeychain['seed'] ?? 0))),
                ];
            }
        }

        $customName = (string)($item['customname'] ?? '');
        if ($customName !== '') {
            // Le nom vient d'un lien arbitraire : on écarte l'UTF-8 invalide,
            // qui ferait échouer les fonctions mb_* et la requête.
            if (preg_match('//u', $customName) !== 1) {
                $customName = '';
            } else {
                $customName = trim((string)preg_replace('/[\x00-\x1F\x7F]/', '', $customName));
                if (function_exists('mb_substr')) {
                    $customName = mb_substr($customName, 0, 20);
                } elseif (preg_match('/^.{0,20}/us', $customName, $cut) === 1) {
                    // Sans mbstring, `.` en mode /u découpe sur des caractères
                    // entiers plutôt qu'au milieu d'une séquence UTF-8.
                    $customName = $cut[0];
                }
            }
        }

        return [
            'ok' => true,
            'error' => null,
            'item' => [
                'defindex' => $expectedDefindex > 0 ? $expectedDefindex : (int)($item['defindex'] ?? 0),
                'paintindex' => $paintIndex,
                'paintwear' => self::clamp($item['paintwear'] ?? 0, 0, 1, 0),
                'paintseed' => max(0, min(1000, (int)($item['paintseed'] ?? 0))),
                'stattrak' => !empty($item['stattrak']),
                'stattrak_count' => max(0, min(999999, (int)($item['stattrak_count'] ?? 0))),
                'customname' => $customName,
                'stickers' => array_values($stickers),
                'keychain' => $keychain,
            ],
        ];
    }

    /**
     * Isole la charge hexadécimale d'une saisie utilisateur.
     */
    public static function extractHex(string $input): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        // Un séparateur encodé colle ses chiffres au début de la charge utile
        // (`..._preview%20001807...` donnerait `20001807...`).
        $input = (string)preg_replace('/%[0-9A-Fa-f]{2}/', ' ', $input);

        // Les liens de marché / inventaire (S...A...D...M...) référencent un item
        // Steam : leur contenu n'est pas transporté par le lien, rien à décoder.
        if (preg_match('/(?:^|[^0-9A-Za-z])S\d{6,}A\d+D\d+/i', $input)) {
            return null;
        }

        if (!preg_match_all('/[0-9A-Fa-f]{' . self::MIN_HEX_LENGTH . ',}/', $input, $matches)) {
            return null;
        }

        // La charge utile est de loin la plus longue suite hexadécimale du lien.
        $hex = '';
        foreach ($matches[0] as $candidate) {
            if (strlen($candidate) > strlen($hex)) {
                $hex = $candidate;
            }
        }

        if ($hex === '' || strlen($hex) % 2 !== 0) {
            return null;
        }

        return strtoupper($hex);
    }

    /**
     * Enveloppe le protobuf : préfixe nul, puis checksum CRC32 en big-endian.
     */
    private static function wrap(string $body): string
    {
        $payload = "\x00" . $body;
        $crc = crc32($payload);
        $checksum = (($crc & 0xFFFF) ^ (strlen($body) * $crc)) & 0xFFFFFFFF;

        return strtoupper(bin2hex($payload . pack('N', $checksum)));
    }

    private static function encodeSticker(array $sticker): string
    {
        $out = self::putVarint(self::S_SLOT, max(0, (int)($sticker['slot'] ?? 0)));
        $out .= self::putVarint(self::S_STICKER_ID, max(0, (int)($sticker['id'] ?? 0)));
        $out .= self::putFloat(self::S_WEAR, (float)($sticker['wear'] ?? 0));
        // Une échelle absente rend le sticker invisible dans le visualiseur :
        // on l'écrit systématiquement, même à la valeur par défaut.
        $out .= self::putFloat(self::S_SCALE, self::clamp($sticker['scale'] ?? 1, 0.2, 5, 1));
        $out .= self::putFloat(self::S_ROTATION, (float)($sticker['rotation'] ?? 0));
        $out .= self::putFloat(self::S_OFFSET_X, (float)($sticker['x'] ?? 0));
        $out .= self::putFloat(self::S_OFFSET_Y, (float)($sticker['y'] ?? 0));

        return $out;
    }

    private static function encodeKeychain(array $keychain): string
    {
        $out = self::putVarint(self::S_SLOT, 0);
        $out .= self::putVarint(self::S_STICKER_ID, max(0, (int)($keychain['id'] ?? 0)));
        $out .= self::putFloat(self::S_OFFSET_X, (float)($keychain['x'] ?? 0));
        $out .= self::putFloat(self::S_OFFSET_Y, (float)($keychain['y'] ?? 0));
        $out .= self::putFloat(self::S_OFFSET_Z, (float)($keychain['z'] ?? 0));
        $out .= self::putVarint(self::S_PATTERN, max(0, (int)($keychain['seed'] ?? 0)));

        return $out;
    }

    private static function decodeSticker(string $buffer): ?array
    {
        $fields = self::parse($buffer);
        if ($fields === null) {
            return null;
        }

        $id = (int)(self::readInt($fields, self::S_STICKER_ID) ?? 0);
        if ($id <= 0) {
            return null;
        }

        return [
            'slot' => (int)(self::readInt($fields, self::S_SLOT) ?? 0),
            'id' => $id,
            'x' => self::readFloat($fields, self::S_OFFSET_X) ?? 0.0,
            'y' => self::readFloat($fields, self::S_OFFSET_Y) ?? 0.0,
            'wear' => self::readFloat($fields, self::S_WEAR) ?? 0.0,
            // Champ omis côté encodeur quand il vaut zéro : sans ce repli,
            // le sticker serait réappliqué avec une échelle nulle.
            'scale' => self::readFloat($fields, self::S_SCALE) ?? 1.0,
            'rotation' => self::readFloat($fields, self::S_ROTATION) ?? 0.0,
        ];
    }

    private static function decodeKeychain(string $buffer): ?array
    {
        $fields = self::parse($buffer);
        if ($fields === null) {
            return null;
        }

        $id = (int)(self::readInt($fields, self::S_STICKER_ID) ?? 0);
        if ($id <= 0) {
            return null;
        }

        return [
            'id' => $id,
            'x' => self::readFloat($fields, self::S_OFFSET_X) ?? 0.0,
            'y' => self::readFloat($fields, self::S_OFFSET_Y) ?? 0.0,
            'z' => self::readFloat($fields, self::S_OFFSET_Z) ?? 0.0,
            'seed' => (int)(self::readInt($fields, self::S_PATTERN) ?? 0),
        ];
    }

    /**
     * Découpe un buffer protobuf en champs bruts.
     *
     * @return array<int,array<int,array{wire:int,value:mixed}>>|null
     */
    private static function parse(string $buffer): ?array
    {
        $fields = [];
        $length = strlen($buffer);
        $position = 0;

        while ($position < $length) {
            $key = self::readVarint($buffer, $position);
            if ($key === null) {
                return null;
            }

            $field = $key >> 3;
            $wire = $key & 0x07;
            if ($field <= 0) {
                return null;
            }

            switch ($wire) {
                case self::WIRE_VARINT:
                    $value = self::readVarint($buffer, $position);
                    if ($value === null) {
                        return null;
                    }
                    break;

                case self::WIRE_FIXED32:
                    if ($position + 4 > $length) {
                        return null;
                    }
                    $value = substr($buffer, $position, 4);
                    $position += 4;
                    break;

                case self::WIRE_FIXED64:
                    if ($position + 8 > $length) {
                        return null;
                    }
                    $value = substr($buffer, $position, 8);
                    $position += 8;
                    break;

                case self::WIRE_BYTES:
                    $size = self::readVarint($buffer, $position);
                    if ($size === null || $size < 0 || $position + $size > $length) {
                        return null;
                    }
                    $value = substr($buffer, $position, $size);
                    $position += $size;
                    break;

                default:
                    return null;
            }

            $fields[$field][] = ['wire' => $wire, 'value' => $value];
        }

        return $fields;
    }

    private static function readInt(array $fields, int $field): ?int
    {
        $entry = $fields[$field][0] ?? null;
        if ($entry === null || $entry['wire'] !== self::WIRE_VARINT) {
            return null;
        }

        return (int)$entry['value'];
    }

    private static function readString(array $fields, int $field): ?string
    {
        $entry = $fields[$field][0] ?? null;
        if ($entry === null || $entry['wire'] !== self::WIRE_BYTES) {
            return null;
        }

        return (string)$entry['value'];
    }

    /**
     * Lit un float, qu'il soit transporté en fixed32 ou en bits dans un varint.
     */
    private static function readFloat(array $fields, int $field): ?float
    {
        $entry = $fields[$field][0] ?? null;
        if ($entry === null) {
            return null;
        }

        if ($entry['wire'] === self::WIRE_FIXED32) {
            $value = unpack('g', $entry['value']);
            return $value === false ? null : (float)$value[1];
        }

        if ($entry['wire'] === self::WIRE_VARINT) {
            return self::bitsToFloat((int)$entry['value']);
        }

        return null;
    }

    private static function putVarint(int $field, int $value): string
    {
        return self::tag($field, self::WIRE_VARINT) . self::varint($value);
    }

    private static function putFloat(int $field, float $value): string
    {
        return self::tag($field, self::WIRE_FIXED32) . pack('g', $value);
    }

    private static function putBytes(int $field, string $value): string
    {
        return self::tag($field, self::WIRE_BYTES) . self::varint(strlen($value)) . $value;
    }

    private static function tag(int $field, int $wire): string
    {
        return self::varint(($field << 3) | $wire);
    }

    private static function varint(int $value): string
    {
        if ($value < 0) {
            $value = 0;
        }

        $out = '';
        do {
            $byte = $value & 0x7F;
            $value >>= 7;
            if ($value > 0) {
                $byte |= 0x80;
            }
            $out .= chr($byte);
        } while ($value > 0);

        return $out;
    }

    private static function readVarint(string $buffer, int &$position): ?int
    {
        $result = 0;
        $shift = 0;
        $length = strlen($buffer);

        while ($position < $length) {
            $byte = ord($buffer[$position]);
            $position++;

            if ($shift < 63) {
                $result |= ($byte & 0x7F) << $shift;
            }
            $shift += 7;

            if (($byte & 0x80) === 0) {
                return $result;
            }
            if ($shift > 70) {
                return null;
            }
        }

        return null;
    }

    private static function floatToBits(float $value): int
    {
        $bits = unpack('V', pack('g', $value));
        return $bits === false ? 0 : (int)$bits[1];
    }

    private static function bitsToFloat(int $bits): float
    {
        $value = unpack('g', pack('V', $bits & 0xFFFFFFFF));
        return $value === false ? 0.0 : (float)$value[1];
    }

    private static function clamp($value, float $min, float $max, float $default): float
    {
        if (!is_numeric($value)) {
            return $default;
        }

        $value = (float)$value;
        if (is_nan($value) || is_infinite($value)) {
            return $default;
        }

        return max($min, min($max, $value));
    }

    private static function normalizeRotation($value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        $value = (float)$value;
        if (is_nan($value) || is_infinite($value)) {
            return 0.0;
        }

        return fmod(fmod($value, 360.0) + 360.0, 360.0);
    }

    private static function failure(string $error): array
    {
        return ['ok' => false, 'error' => $error, 'item' => null];
    }
}
