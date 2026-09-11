<?php

namespace Tests\Unit\Game\Gems\Progression\Services;

use App\Game\Gems\Progression\Services\GemScrollEffectService;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateCharacterGameMapGemScroll;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;

class GemScrollEffectServiceTest extends TestCase
{
    use CreateCharacter, CreateCharacterGameMapGemScroll, CreateGameMapGemParamter, CreateItem, CreateUser, RefreshDatabase;

    private GemScrollEffectService $gemScrollEffectService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gemScrollEffectService = new GemScrollEffectService;
    }

    public function test_active_scroll_contributes_to_the_aggregate(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $scrollItem = $this->createGemXpScrollItem(0.10);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'item_id' => $scrollItem->id,
        ]);

        $aggregate = $this->gemScrollEffectService->aggregateForMapProfile($character, $profile);

        $this->assertEqualsWithDelta(0.10, $aggregate->xpBonusTotal(), 0.0000001);
        $this->assertEqualsWithDelta(0.10, $aggregate->totalPrimaryBonus(), 0.0000001);
        $this->assertSame(1, $aggregate->activeCount());
    }

    public function test_expired_scroll_contributes_zero(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $scrollItem = $this->createGemXpScrollItem(0.10);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'item_id' => $scrollItem->id,
            'started_at' => now()->subHours(5),
            'expires_at' => now()->subHour(),
        ]);

        $aggregate = $this->gemScrollEffectService->aggregateForMapProfile($character, $profile);

        $this->assertSame(0, $aggregate->activeCount());
        $this->assertEqualsWithDelta(0.0, $aggregate->totalPrimaryBonus(), 0.0000001);
    }

    public function test_effects_are_isolated_by_character(): void
    {
        $userOne = $this->createUser();
        $characterOne = $this->createCharacter(['user_id' => $userOne->id, 'name' => 'Character One']);
        $userTwo = $this->createUser();
        $characterTwo = $this->createCharacter(['user_id' => $userTwo->id, 'name' => 'Character Two']);
        $profile = $this->createGameMapGemParamter();
        $scrollItem = $this->createGemXpScrollItem(0.10);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $characterOne->id,
            'game_map_gem_paramter_id' => $profile->id,
            'item_id' => $scrollItem->id,
        ]);

        $aggregate = $this->gemScrollEffectService->aggregateForMapProfile($characterTwo, $profile);

        $this->assertSame(0, $aggregate->activeCount());
    }

    public function test_effects_are_isolated_by_profile(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profileOne = $this->createGameMapGemParamter();
        $profileTwo = $this->createGameMapGemParamter();
        $scrollItem = $this->createGemXpScrollItem(0.10);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profileOne->id,
            'item_id' => $scrollItem->id,
        ]);

        $aggregate = $this->gemScrollEffectService->aggregateForMapProfile($character, $profileTwo);

        $this->assertSame(0, $aggregate->activeCount());
    }

    public function test_currency_aggregates_remain_separated_by_currency(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $goldScroll = $this->createGemCurrencyScrollItem(0.10, GemScrollCurrencyType::GOLD);
        $shardsScroll = $this->createGemCurrencyScrollItem(0.05, GemScrollCurrencyType::SHARDS);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'item_id' => $goldScroll->id,
        ]);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'item_id' => $shardsScroll->id,
        ]);

        $aggregate = $this->gemScrollEffectService->aggregateForMapProfile($character, $profile);

        $this->assertEqualsWithDelta(0.10, $aggregate->goldBonusTotal(), 0.0000001);
        $this->assertEqualsWithDelta(0.05, $aggregate->shardsBonusTotal(), 0.0000001);
        $this->assertEqualsWithDelta(0.0, $aggregate->copperCoinBonusTotal(), 0.0000001);
        $this->assertEqualsWithDelta(0.0, $aggregate->goldDustBonusTotal(), 0.0000001);
    }

    public function test_item_secondary_chances_clamp_at_one_hundred_percent(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $itemScrollOne = $this->createGemItemScrollItem(0.02, 0.6, 0.0);
        $itemScrollTwo = $this->createGemItemScrollItem(0.02, 0.6, 0.0);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'item_id' => $itemScrollOne->id,
        ]);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'item_id' => $itemScrollTwo->id,
        ]);

        $aggregate = $this->gemScrollEffectService->aggregateForMapProfile($character, $profile);

        $this->assertEqualsWithDelta(1.0, $aggregate->itemSocketChance(), 0.0000001);
    }

    public function test_secondary_item_chances_do_not_consume_primary_budget(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $itemScroll = $this->createGemItemScrollItem(0.02, 0.5, 0.5);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'item_id' => $itemScroll->id,
        ]);

        $aggregate = $this->gemScrollEffectService->aggregateForMapProfile($character, $profile);

        $this->assertEqualsWithDelta(0.02, $aggregate->totalPrimaryBonus(), 0.0000001);
    }

    public function test_primary_bonus_total_sums_across_all_three_scroll_families(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $xpScroll = $this->createGemXpScrollItem(0.10);
        $currencyScroll = $this->createGemCurrencyScrollItem(0.05, GemScrollCurrencyType::GOLD);
        $itemScroll = $this->createGemItemScrollItem(0.02);
        $this->createCharacterGameMapGemScroll(['character_id' => $character->id, 'game_map_gem_paramter_id' => $profile->id, 'item_id' => $xpScroll->id]);
        $this->createCharacterGameMapGemScroll(['character_id' => $character->id, 'game_map_gem_paramter_id' => $profile->id, 'item_id' => $currencyScroll->id]);
        $this->createCharacterGameMapGemScroll(['character_id' => $character->id, 'game_map_gem_paramter_id' => $profile->id, 'item_id' => $itemScroll->id]);

        $aggregate = $this->gemScrollEffectService->aggregateForMapProfile($character, $profile);

        $this->assertEqualsWithDelta(0.17, $aggregate->totalPrimaryBonus(), 0.0000001);
    }

    public function test_can_activate_rejects_a_bonus_that_would_exceed_the_shared_cap(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $scrollItem = $this->createGemXpScrollItem(19.5);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'item_id' => $scrollItem->id,
        ]);

        $aggregate = $this->gemScrollEffectService->aggregateForMapProfile($character, $profile);

        $this->assertTrue($aggregate->canActivate(0.5));
        $this->assertFalse($aggregate->canActivate(0.6));
    }
}
