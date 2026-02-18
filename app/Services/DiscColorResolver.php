<?php

namespace App\Services;

use App\Models\Color;

class DiscColorResolver
{
    /** @var array<string, string> hex or 'transparent' => color name for DB */
    private const HEX_TO_NAME = [
        '#dc2626' => 'Red',
        '#2563eb' => 'Blue',
        '#16a34a' => 'Green',
        '#eab308' => 'Yellow',
        '#ea580c' => 'Orange',
        '#db2777' => 'Pink',
        '#7c3aed' => 'Purple',
        '#ffffff' => 'White',
        '#1f2937' => 'Black',
        'transparent' => 'Clear / Transparent',
    ];

    /**
     * Resolve selected color values (hex or "transparent") to color IDs, creating colors if needed.
     *
     * @param  array<int, string>  $selectedColors
     * @return array<int, int>
     */
    public static function resolveToColorIds(array $selectedColors): array
    {
        $names = [];
        foreach ($selectedColors as $value) {
            $name = self::HEX_TO_NAME[$value] ?? $value;
            if ($name !== '' && ! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        $ids = [];
        foreach ($names as $name) {
            $color = Color::firstOrCreate(['name' => $name]);
            $ids[] = $color->id;
        }

        return $ids;
    }
}
