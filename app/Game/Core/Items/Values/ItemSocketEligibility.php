<?php

namespace App\Game\Core\Items\Values;

/**
 * The one shared rule for which Item types may receive random sockets and
 * the maximum socket count any Item may hold. Reused by every reward path
 * that randomly assigns sockets to a generated Item.
 */
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
     */
    public function isEligible(string $itemType): bool
    {
        return in_array($itemType, self::ELIGIBLE_TYPES, true);
    }

    /**
     * The maximum socket count any Item may hold.
     */
    public function maxSocketCount(): int
    {
        return self::MAX_SOCKET_COUNT;
    }
}
