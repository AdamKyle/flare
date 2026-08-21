<?php

namespace App\Game\Automation\BatchCrafting\Enums;

use App\Game\Skills\Values\CraftingSkillGroup;

enum CraftSetPosition: string
{
    case LEFT_HAND = 'left_hand';
    case RIGHT_HAND = 'right_hand';
    case BODY = 'body';
    case LEGGINGS = 'leggings';
    case SLEEVES = 'sleeves';
    case GLOVES = 'gloves';
    case FEET = 'feet';
    case HELMET = 'helmet';
    case RING_ONE = 'ring_0';
    case RING_TWO = 'ring_1';
    case DAMAGE_SPELL = 'spell-damage';
    case HEALING_SPELL = 'spell-healing';

    /**
     * Return the full authoritative Craft Set position order.
     *
     * @return array<int, self> The ordered position cases.
     */
    public static function orderedCases(): array
    {
        return self::cases();
    }

    /**
     * Determine whether this position is required for a valid Craft Set plan.
     *
     * @return bool True when the position must always be filled.
     */
    public function isRequired(): bool
    {
        return ! $this->isHandPosition();
    }

    /**
     * Determine whether this position is one of the two optional hand positions.
     *
     * @return bool True for the left/right hand positions.
     */
    public function isHandPosition(): bool
    {
        return $this === self::LEFT_HAND || $this === self::RIGHT_HAND;
    }

    /**
     * Return the Crafting skill group fixed by this position, when the position has one.
     *
     * Hand positions have no fixed group; their crafting type is determined by the
     * resolved item (weapon or shield) instead.
     *
     * @return CraftingSkillGroup|null The fixed Crafting skill group, or null for hand positions.
     */
    public function craftingGroup(): ?CraftingSkillGroup
    {
        return match ($this) {
            self::LEFT_HAND, self::RIGHT_HAND => null,
            self::BODY, self::LEGGINGS, self::SLEEVES, self::GLOVES, self::FEET, self::HELMET => CraftingSkillGroup::ARMOUR,
            self::RING_ONE, self::RING_TWO => CraftingSkillGroup::RING,
            self::DAMAGE_SPELL, self::HEALING_SPELL => CraftingSkillGroup::SPELL,
        };
    }

    /**
     * Return the required resolved item type for this position, when the position has one.
     *
     * Hand positions have no fixed item type; they are validated through
     * SetHandsValidation instead.
     *
     * @return string|null The required item type, or null for hand positions.
     */
    public function requiredItemType(): ?string
    {
        return match ($this) {
            self::LEFT_HAND, self::RIGHT_HAND => null,
            self::BODY => 'body',
            self::LEGGINGS => 'leggings',
            self::SLEEVES => 'sleeves',
            self::GLOVES => 'gloves',
            self::FEET => 'feet',
            self::HELMET => 'helmet',
            self::RING_ONE, self::RING_TWO => 'ring',
            self::DAMAGE_SPELL => 'spell-damage',
            self::HEALING_SPELL => 'spell-healing',
        };
    }
}
