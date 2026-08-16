<?php

namespace App\Game\Automation\BatchCrafting\Enums;

/**
 * Mirrors the raw `progress.craft_mode` value already stored/validated for the
 * Craft batch type. AMOUNT keeps the existing `specific_item` backend value so
 * stored progress data and API compatibility are unaffected.
 */
enum CraftingBatchMode: string
{
    case AMOUNT = 'specific_item';
}
