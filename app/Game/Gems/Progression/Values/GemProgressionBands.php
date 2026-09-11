<?php

namespace App\Game\Gems\Progression\Values;

/**
 * The closed set of level-band constants shared by Gem progression math, the
 * Gem Scroll generator, and their tests. Numeric thresholds/ranges live here
 * once so no other class duplicates them.
 */
class GemProgressionBands
{
    public const int GLOBAL_MIN_LEVEL = 1;

    public const int GLOBAL_MAX_LEVEL = 100;

    public const int PERSONAL_MIN_LEVEL = 1;

    public const int PERSONAL_BASE_CAP_LEVEL = 100;

    public const int PERSONAL_NEGATIVE_BAND_ONE_LEVEL = 200;

    public const int PERSONAL_RARITY_UNLOCK_LEVEL = 200;

    public const int PERSONAL_NEGATIVE_BAND_TWO_LEVEL = 300;

    public const int PERSONAL_RARITY_MID_LEVEL = 300;

    public const int PERSONAL_NEGATIVE_BAND_THREE_LEVEL = 500;

    public const int PERSONAL_RARITY_UNIQUE_MYTHIC_MAX_LEVEL = 500;

    public const int PERSONAL_COSMIC_UNLOCK_LEVEL = 500;

    public const int PERSONAL_NEGATIVE_BAND_FOUR_LEVEL = 700;

    public const int PERSONAL_COSMIC_MAX_LEVEL = 700;

    public const int PERSONAL_ENHANCED_EQUIPMENT_LEVEL = 700;

    public const int PERSONAL_NEGATIVE_BAND_FIVE_LEVEL = 1000;

    public const int PERSONAL_MAX_LEVEL = 1000;

    public const int PERSONAL_CURVE_MID_LEVEL = 100;

    public const int PERSONAL_CURVE_HIGH_LEVEL = 500;

    public const float PERSONAL_NEGATIVE_BAND_INCREMENT = 0.02;

    public const float PERSONAL_NEGATIVE_BAND_ONE_INCREMENT = 0.03;

    public const float PERSONAL_RARITY_UNIQUE_MYTHIC_MID_VALUE = 0.02;

    public const float PERSONAL_RARITY_UNIQUE_MYTHIC_MAX_VALUE = 0.10;

    public const float PERSONAL_COSMIC_MIN_VALUE = 0.01;

    public const float PERSONAL_COSMIC_MAX_VALUE = 0.08;

    public const float PERSONAL_ENHANCED_EQUIPMENT_CHANCE = 0.01;

    public const float GEM_SCROLL_DROP_CHANCE = 0.02;

    public const float GEM_SCROLL_BASE_XP_MULTIPLIER = 1.05;

    public const float ACTIVE_SCROLL_PRIMARY_BONUS_CAP = 20.0;

    public const int ITEM_SCROLL_MAX_BONUS_OPPORTUNITIES = 5;

    public const float ITEM_SCROLL_OPPORTUNITY_BONUS_STEP = 1.0;

    public const float GEM_TEST_SCROLL_DEFAULT_XP_BONUS = 0.10;

    public const float GEM_TEST_SCROLL_DEFAULT_CURRENCY_BONUS = 0.10;

    public const float GEM_TEST_SCROLL_DEFAULT_ITEM_BONUS = 0.02;

    public const float GEM_TEST_SCROLL_DEFAULT_SOCKET_CHANCE = 0.02;

    public const float GEM_TEST_SCROLL_DEFAULT_PRE_GEM_CHANCE = 0.01;

    public const int GEM_TEST_SCROLL_DEFAULT_DURATION_MINUTES = 120;
}
