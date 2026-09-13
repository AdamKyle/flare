<?php

namespace App\Game\Gems\Values;

use RuntimeException;

class GemWorldNameValidator
{
    private const FORBIDDEN_PHRASES = ['Gem Profile', 'Location Gem World', 'Map Gem World'];

    /**
     * Assert a Gem World Name does not contain a technical/generated-name phrase.
     */
    public static function assertNotTechnical(string $gemWorldName, int $rowNumber): void
    {
        foreach (self::FORBIDDEN_PHRASES as $phrase) {
            if (stripos($gemWorldName, $phrase) !== false) {
                throw new RuntimeException('Row '.$rowNumber.': "gem_world_name" must not contain the technical phrase "'.$phrase.'".');
            }
        }
    }

    /**
     * Assert a Location Gem World Name does not contain its source Location name.
     */
    public static function assertDoesNotContainLocationName(string $gemWorldName, string $locationName, int $rowNumber): void
    {
        if (stripos($gemWorldName, $locationName) !== false) {
            throw new RuntimeException('Row '.$rowNumber.': Gem World Name "'.$gemWorldName.'" must not contain the source Location name "'.$locationName.'".');
        }
    }

    /**
     * Assert every Gem World Name in this workbook is unique, case-insensitively, both against
     * every other row in the same workbook and against every Gem World Name already persisted
     * for the other Gem profile type.
     */
    public static function assertUniqueAcrossWorkbookAndOtherProfileType(array $gemWorldNamesByRow, string $otherProfileModelClass): void
    {
        $seen = [];

        foreach ($gemWorldNamesByRow as $rowNumber => $gemWorldName) {
            $key = mb_strtolower($gemWorldName);

            if (isset($seen[$key])) {
                throw new RuntimeException('Row '.$rowNumber.': duplicate Gem World Name "'.$gemWorldName.'" also used on row '.$seen[$key].'.');
            }

            $seen[$key] = $rowNumber;
        }

        $otherProfileModelClass::query()->whereNotNull('gem_world_name')->pluck('gem_world_name')
            ->each(function (string $otherGemWorldName) use ($seen): void {
                $key = mb_strtolower($otherGemWorldName);

                if (isset($seen[$key])) {
                    throw new RuntimeException('Gem World Name "'.$otherGemWorldName.'" is already used by another Gem profile type.');
                }
            });
    }
}
