<?php

namespace App\Game\Core\Items\Values;

class ItemSocketEligibility
{
    public const int MAX_SOCKET_COUNT = 6;

    /**
     * @var array
     */
    private const array ELIGIBLE_TYPES = [
        'weapon',
        'sleeves',
        'gloves',
        'feet',
        'body',
        'shield',
        'helmet',
    ];

    /**
     * Determine whether the given Item type may receive random sockets.
     *
     * @param string $itemType
     * @return bool
     */
    public function isEligible(string $itemType): bool
    {
        return in_array($itemType, self::ELIGIBLE_TYPES, true);
    }

    /**
     * The closed set of Item types that may receive random sockets.
     *
     * @return array
     */
    public function eligibleTypes(): array
    {
        return self::ELIGIBLE_TYPES;
    }

    /**
     * The maximum socket count any Item may hold.
     *
     * @return int
     */
    public function maxSocketCount(): int
    {
        return self::MAX_SOCKET_COUNT;
    }
}
