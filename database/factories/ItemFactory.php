<?php

namespace Database\Factories;

use App\Flare\Models\Item;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use App\Game\Gems\Progression\Values\GemScrollType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    private const DEFAULT_XP_SCROLL_BONUS = 0.10;

    private const DEFAULT_CURRENCY_SCROLL_BONUS = 0.10;

    private const DEFAULT_ITEM_SCROLL_BONUS = 0.02;

    private const DEFAULT_SCROLL_SOCKET_CHANCE = 0.02;

    private const DEFAULT_SCROLL_PRE_GEM_CHANCE = 0.01;

    private const DEFAULT_SCROLL_DURATION_MINUTES = 120;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'test',
            'type' => 'weapon',
            'base_damage' => 10,
            'cost' => 100,
            'crafting_type' => 'weapon',
            'description' => 'sample',
            'can_resurrect' => false,
            'resurrection_chance' => 0.0,
            'can_use_on_other_items' => false,
            'market_sellable' => true,
        ];
    }

    /**
     * State a generated XP Gem Scroll Item using the same persisted semantics as production.
     */
    public function gemXpScroll(
        float $bonus = self::DEFAULT_XP_SCROLL_BONUS,
        int $lastsForMinutes = self::DEFAULT_SCROLL_DURATION_MINUTES,
    ): static {
        return $this->state([
            'name' => 'Gem Experience Scroll',
            'type' => 'alchemy',
            'randomly_generated' => true,
            'usable' => true,
            'can_craft' => false,
            'market_sellable' => false,
            'can_drop' => false,
            'can_stack' => false,
            'lasts_for' => $lastsForMinutes,
            'gem_scroll_type' => GemScrollType::XP,
            'gem_scroll_bonus' => $bonus,
            'gem_scroll_currency_type' => null,
            'gem_scroll_socket_chance' => null,
            'gem_scroll_pre_gem_chance' => null,
        ]);
    }

    /**
     * State a generated Currency Gem Scroll Item using the same persisted semantics as production.
     */
    public function gemCurrencyScroll(
        float $bonus = self::DEFAULT_CURRENCY_SCROLL_BONUS,
        GemScrollCurrencyType $currencyType = GemScrollCurrencyType::GOLD,
        int $lastsForMinutes = self::DEFAULT_SCROLL_DURATION_MINUTES,
    ): static {
        return $this->state([
            'name' => 'Gold Gem Scroll',
            'type' => 'alchemy',
            'randomly_generated' => true,
            'usable' => true,
            'can_craft' => false,
            'market_sellable' => false,
            'can_drop' => false,
            'can_stack' => false,
            'lasts_for' => $lastsForMinutes,
            'gem_scroll_type' => GemScrollType::CURRENCY,
            'gem_scroll_bonus' => $bonus,
            'gem_scroll_currency_type' => $currencyType,
            'gem_scroll_socket_chance' => null,
            'gem_scroll_pre_gem_chance' => null,
        ]);
    }

    /**
     * State a generated Item Gem Scroll Item using the same persisted semantics as production.
     */
    public function gemItemScroll(
        float $bonus = self::DEFAULT_ITEM_SCROLL_BONUS,
        float $socketChance = self::DEFAULT_SCROLL_SOCKET_CHANCE,
        float $preGemChance = self::DEFAULT_SCROLL_PRE_GEM_CHANCE,
        int $lastsForMinutes = self::DEFAULT_SCROLL_DURATION_MINUTES,
    ): static {
        return $this->state([
            'name' => 'Gem Item Scroll',
            'type' => 'alchemy',
            'randomly_generated' => true,
            'usable' => true,
            'can_craft' => false,
            'market_sellable' => false,
            'can_drop' => false,
            'can_stack' => false,
            'lasts_for' => $lastsForMinutes,
            'gem_scroll_type' => GemScrollType::ITEM,
            'gem_scroll_bonus' => $bonus,
            'gem_scroll_currency_type' => null,
            'gem_scroll_socket_chance' => $socketChance,
            'gem_scroll_pre_gem_chance' => $preGemChance,
        ]);
    }
}
