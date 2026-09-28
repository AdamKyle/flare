<?php

namespace App\Game\Core\Items\Values;

class ItemSocketEligibility
{
    private const array MAXIMUM_SOCKET_COUNTS = [
        'body' => 6,
        'helmet' => 1,
        'sleeves' => 1,
        'gloves' => 1,
        'feet' => 1,
        'leggings' => 1,
        'shield' => 3,
        ItemType::BOW->value => 6,
        ItemType::STAVE->value => 6,
        ItemType::HAMMER->value => 6,
        ItemType::WEAPON->value => 3,
        ItemType::DAGGER->value => 3,
        ItemType::SCRATCH_AWL->value => 3,
        ItemType::MACE->value => 3,
        ItemType::GUN->value => 3,
        ItemType::FAN->value => 3,
        ItemType::WAND->value => 3,
        ItemType::CENSER->value => 3,
        ItemType::CLAW->value => 3,
        ItemType::SWORD->value => 3,
    ];

    private const array TWO_HANDED_TYPES = [
        ItemType::BOW->value,
        ItemType::STAVE->value,
        ItemType::HAMMER->value,
    ];

    private const array ONE_SOCKET_ARMOUR_TYPES = [
        'helmet',
        'sleeves',
        'gloves',
        'feet',
        'leggings',
    ];

    /**
     * Determine whether the Item type may receive sockets.
     *
     * @param string $itemType
     * @return bool
     */
    public function isEligible(string $itemType): bool
    {
        return array_key_exists($itemType, self::MAXIMUM_SOCKET_COUNTS);
    }

    /**
     * Return the maximum sockets allowed for the Item type.
     *
     * @param string $itemType
     * @return int
     */
    public function maxSocketCount(string $itemType): int
    {
        return self::MAXIMUM_SOCKET_COUNTS[$itemType] ?? 0;
    }

    /**
     * Determine whether the Item type is a two-handed physical weapon.
     *
     * @param string $itemType
     * @return bool
     */
    public function isTwoHanded(string $itemType): bool
    {
        return in_array($itemType, self::TWO_HANDED_TYPES, true);
    }

    /**
     * Determine whether the Item type is armour limited to one socket.
     *
     * @param string $itemType
     * @return bool
     */
    public function isOneSocketArmour(string $itemType): bool
    {
        return in_array($itemType, self::ONE_SOCKET_ARMOUR_TYPES, true);
    }

    /**
     * Return every Item type eligible to receive sockets.
     *
     * @return array
     */
    public function eligibleTypes(): array
    {
        return array_keys(self::MAXIMUM_SOCKET_COUNTS);
    }
}
