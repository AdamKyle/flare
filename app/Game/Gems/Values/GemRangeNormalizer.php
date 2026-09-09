<?php

namespace App\Game\Gems\Values;

class GemRangeNormalizer
{
    private const RANGE_PATTERN = '/^\d+(?:\.\d+)?-\d+(?:\.\d+)?$/';

    private const ABSENT_PATTERN = '/^0+(?:\.0+)?(?:-0+(?:\.0+)?)?$/';

    /**
     * A blank value, or an all-zero value/range, represents "no effect configured".
     */
    public static function isAbsent(?string $value): bool
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' || preg_match(self::ABSENT_PATTERN, $trimmed) === 1;
    }

    /**
     * Determine whether the trimmed value is a syntactically valid "min-max" range.
     */
    public static function isValidFormat(string $value): bool
    {
        return preg_match(self::RANGE_PATTERN, trim($value)) === 1;
    }

    /**
     * Determine whether a syntactically valid range has its minimum at or below its maximum.
     */
    public static function isOrdered(string $value): bool
    {
        [$min, $max] = explode('-', trim($value), 2);

        return (float) $min <= (float) $max;
    }

    /**
     * Normalize a raw range value into the value that should be persisted:
     * null when absent (blank or all-zero), the trimmed value otherwise.
     */
    public static function normalize(?string $value): ?string
    {
        return self::isAbsent($value) ? null : trim((string) $value);
    }
}
