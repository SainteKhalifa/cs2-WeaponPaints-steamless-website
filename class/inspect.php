<?php

/**
 * CS2 inspect link encoder and decoder (CEconItemPreviewDataBlock).
 *
 * Bridges a `wp_player_skins` row and an external 3D viewer: the loadout is
 * encoded into an inspect link, the player arranges stickers and charm in the
 * viewer, and the link they bring back is decoded here.
 *
 * The protobuf is written and parsed by hand, so the class has no dependency.
 */
class InspectLink
{
    public const VIEWER_URL = 'https://skincraft.gg/i/';

    /** CEconItemPreviewDataBlock fields. */
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

    /** Fields of the Sticker sub-message, reused for keychains. */
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

    /** Quality 9 is Strange, the canonical way an item carries StatTrak. */
    private const QUALITY_STRANGE = 9;

    /** Shortest hexadecimal envelope that could plausibly hold an item. */
    private const MIN_HEX_LENGTH = 32;

    /**
     * Range allowed for keychain offsets.
     *
     * Stickers sit in a unit square, but a charm is positioned in world units:
     * a genuine inspect link routinely carries values around 10. Clamping those
     * to 1 would drag every imported charm back to the same wrong spot.
     */
    private const KEYCHAIN_OFFSET_LIMIT = 100;

    public static function viewerUrl(string $hex): string
    {
        return self::VIEWER_URL . strtoupper($hex);
    }

    /**
     * Builds a normalised item from a `wp_player_skins` row.
     *
     * @param array      $row      Database row (weapon_defindex, weapon_paint_id, ...).
     * @param array      $stickers Slot-indexed ['id','x','y','wear','scale','rotation'].
     * @param array|null $keychain ['id','x','y','z','seed'], or null for none.
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
     * Encodes a normalised item into its hexadecimal envelope.
     */
    public static function encode(array $item): string
    {
        $body = '';
        $body .= self::putVarint(self::F_DEFINDEX, max(0, (int)($item['defindex'] ?? 0)));
        $body .= self::putVarint(self::F_PAINTINDEX, max(0, (int)($item['paintindex'] ?? 0)));

        if (!empty($item['stattrak'])) {
            $body .= self::putVarint(self::F_QUALITY, self::QUALITY_STRANGE);
        }

        // paintwear is a uint32 carrying the bits of a float, not an integer.
        $body .= self::putVarint(self::F_PAINTWEAR, self::floatToBits((float)($item['paintwear'] ?? 0)));
        $body .= self::putVarint(self::F_PAINTSEED, max(0, (int)($item['paintseed'] ?? 0)));

        if (!empty($item['stattrak'])) {
            // The counter only shows when killeaterscoretype is present, even at zero.
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
     * Decodes an inspect link into a normalised item.
     *
     * Accepts a full viewer URL, a `steam://` link, or the bare hex payload.
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

        // Some links are masked, in which case the leading byte is the XOR key.
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
     * Checks a decoded item against the site's own reference data.
     *
     * A player can forge any link they like, so nothing that leaves the decoder
     * may reach the database without being cross-checked here.
     *
     * @param array $reference [
     *     'defindex'  => int,   weapon currently being edited
     *     'paints'    => array, paint ids allowed for that weapon
     *     'stickers'  => array, known sticker ids
     *     'keychains' => array, known charm ids
     *     'slots'     => int,   sticker slots the weapon accepts
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
                'customname' => self::sanitizeName($item['customname'] ?? ''),
                'stickers' => self::sanitizeStickers(
                    $item['stickers'] ?? [],
                    $reference['stickers'] ?? [],
                    max(0, min(5, (int)($reference['slots'] ?? 5)))
                ),
                'keychain' => self::sanitizeKeychain(
                    $item['keychain'] ?? null,
                    $reference['keychains'] ?? []
                ),
            ],
        ];
    }

    /**
     * Keeps every known sticker and settles the slot each one ends up in.
     *
     * A viewer may announce two stickers on the same slot, or a slot number the
     * weapon does not have. Indexing blindly by slot would silently drop one, so
     * whatever cannot keep its announced slot moves to the first free one.
     *
     * @return array<int,array> Ordered list, each entry carrying its final slot.
     */
    private static function sanitizeStickers(array $stickers, array $known, int $slotCount): array
    {
        $placed = [];
        $displaced = [];

        foreach ($stickers as $sticker) {
            $id = (int)($sticker['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if ($known && !array_key_exists($id, $known)) {
                continue;
            }

            $entry = [
                'slot' => 0,
                'id' => $id,
                'x' => self::clamp($sticker['x'] ?? 0, -1, 1, 0),
                'y' => self::clamp($sticker['y'] ?? 0, -1, 1, 0),
                'wear' => self::clamp($sticker['wear'] ?? 0, 0, 1, 0),
                'scale' => self::clamp($sticker['scale'] ?? 1, 0.2, 5, 1),
                'rotation' => self::normalizeRotation($sticker['rotation'] ?? 0),
            ];

            $slot = (int)($sticker['slot'] ?? 0);
            if ($slot >= 0 && $slot < $slotCount && !isset($placed[$slot])) {
                $entry['slot'] = $slot;
                $placed[$slot] = $entry;
            } else {
                $displaced[] = $entry;
            }
        }

        foreach ($displaced as $entry) {
            for ($slot = 0; $slot < $slotCount; $slot++) {
                if (!isset($placed[$slot])) {
                    $entry['slot'] = $slot;
                    $placed[$slot] = $entry;
                    break;
                }
            }
        }

        ksort($placed);

        return array_values($placed);
    }

    private static function sanitizeKeychain($keychain, array $known): ?array
    {
        if (!is_array($keychain)) {
            return null;
        }

        $id = (int)($keychain['id'] ?? 0);
        if ($id <= 0 || ($known && !array_key_exists($id, $known))) {
            return null;
        }

        $limit = self::KEYCHAIN_OFFSET_LIMIT;

        return [
            'id' => $id,
            'x' => self::clamp($keychain['x'] ?? 0, -$limit, $limit, 0),
            'y' => self::clamp($keychain['y'] ?? 0, -$limit, $limit, 0),
            'z' => self::clamp($keychain['z'] ?? 0, -$limit, $limit, 0),
            'seed' => max(0, min(100000, (int)($keychain['seed'] ?? 0))),
        ];
    }

    /**
     * Trims a custom name down to something safe to store.
     *
     * The name arrives from an arbitrary link, so invalid UTF-8 is discarded
     * rather than passed on to the mb_* functions and the database.
     */
    private static function sanitizeName($name): string
    {
        $name = (string)$name;
        if ($name === '' || preg_match('//u', $name) !== 1) {
            return '';
        }

        $name = trim((string)preg_replace('/[\x00-\x1F\x7F]/', '', $name));
        if (function_exists('mb_substr')) {
            return mb_substr($name, 0, 20);
        }

        // Without mbstring, `.` under /u still cuts on whole characters rather
        // than in the middle of a UTF-8 sequence.
        return preg_match('/^.{0,20}/us', $name, $cut) === 1 ? $cut[0] : '';
    }

    /**
     * Picks the hexadecimal payload out of whatever the player pasted.
     */
    public static function extractHex(string $input): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        // An encoded separator would glue its own digits to the front of the
        // payload: `..._preview%20001807...` would yield `20001807...`.
        $input = (string)preg_replace('/%[0-9A-Fa-f]{2}/', ' ', $input);

        // Market and inventory links (S...A...D...M...) point at a Steam item;
        // the link carries no item data, so there is nothing to decode.
        if (preg_match('/(?:^|[^0-9A-Za-z])S\d{6,}A\d+D\d+/i', $input)) {
            return null;
        }

        if (!preg_match_all('/[0-9A-Fa-f]{' . self::MIN_HEX_LENGTH . ',}/', $input, $matches)) {
            return null;
        }

        // The payload is by far the longest run of hex digits in the link.
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
     * Wraps the protobuf: null prefix, then a big-endian CRC32 checksum.
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
        // A missing scale leaves the sticker invisible in the viewer, so it is
        // always written, even at its default value.
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
            // Encoders omit this field when it is zero. Without the fallback the
            // sticker would come back with no scale at all, hence invisible.
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
     * Splits a protobuf buffer into its raw fields.
     *
     * @return array<int,array<int,array{wire:int,value:mixed}>>|null Null on malformed input.
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
     * Reads a float, whether it arrived as fixed32 or as bits inside a varint.
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
