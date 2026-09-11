<?php

namespace Database\Factories;

use App\Flare\Models\Item;
use App\Game\Gems\Progression\Values\GemProgressionBands;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use App\Game\Gems\Progression\Values\GemScrollType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
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
        ];
    }

    /**
     * State a generated XP Gem Scroll Item using the same persisted semantics as production.
     */
    public function gemXpScroll(
        float $bonus = GemProgressionBands::GEM_TEST_SCROLL_DEFAULT_XP_BONUS,
        int $lastsForMinutes = GemProgressionBands::GEM_TEST_SCROLL_DEFAULT_DURATION_MINUTES,
    ): static {
        return $this->state(fn (): array => [
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
        float $bonus = GemProgressionBands::GEM_TEST_SCROLL_DEFAULT_CURRENCY_BONUS,
        GemScrollCurrencyType $currencyType = GemScrollCurrencyType::GOLD,
        int $lastsForMinutes = GemProgressionBands::GEM_TEST_SCROLL_DEFAULT_DURATION_MINUTES,
    ): static {
        return $this->state(fn (): array => [
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
        float $bonus = GemProgressionBands::GEM_TEST_SCROLL_DEFAULT_ITEM_BONUS,
        float $socketChance = GemProgressionBands::GEM_TEST_SCROLL_DEFAULT_SOCKET_CHANCE,
        float $preGemChance = GemProgressionBands::GEM_TEST_SCROLL_DEFAULT_PRE_GEM_CHANCE,
        int $lastsForMinutes = GemProgressionBands::GEM_TEST_SCROLL_DEFAULT_DURATION_MINUTES,
    ): static {
        return $this->state(fn (): array => [
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
