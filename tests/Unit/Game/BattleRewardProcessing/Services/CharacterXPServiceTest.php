<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Game\BattleRewardProcessing\Services\CharacterXPService;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Reincarnate\Values\MaxReincarnationStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMaxLevelConfiguration;
use Tests\Traits\CreateMonster;

class CharacterXPServiceTest extends TestCase
{
    use CreateCharacterBoon, CreateGameMapGemParamter, CreateGem, CreateItem, CreateMaxLevelConfiguration, CreateMonster, RefreshDatabase;

    public function test_monster_xp_increase_from_rolled_map_gem_doubles_awarded_monster_xp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 5]);
        $gameMap = $character->map->gameMap;

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'xp' => 1000,
            'max_level' => 999,
        ]);

        $service = resolve(CharacterXPService::class);

        $xpWithoutGem = $service->setCharacter($character->refresh())->fetchXpForMonster($monster);

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['monster_xp_increase' => 1.0]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $xpWithGem = $service->setCharacter($character->refresh())->fetchXpForMonster($monster);

        $this->assertSame($xpWithoutGem * 2, $xpWithGem);
    }

    public function test_character_xp_bonus_from_rolled_map_gem_applies_exactly_once(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 5]);
        $gameMap = $character->map->gameMap;

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'xp' => 1000,
            'max_level' => 999,
        ]);

        $service = resolve(CharacterXPService::class);

        $xpWithoutGem = $service->setCharacter($character->refresh())->fetchXpForMonster($monster);

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['character_xp_bonus' => 0.5]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $xpWithGem = $service->setCharacter($character->refresh())->fetchXpForMonster($monster);

        $this->assertSame((int) round($xpWithoutGem * 1.5), $xpWithGem);
    }

    public function test_monster_xp_beyond_the_platform_integer_range_fails_explicitly_instead_of_awarding_fake_xp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 5]);
        $gameMap = $character->map->gameMap;

        // monsters.xp is a bigint column, so a value near PHP_INT_MAX is a valid row, but
        // the Gem-adjusted multiplication below pushes the calculation past PHP_INT_MAX.
        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'xp' => 9_000_000_000_000_000_000,
            'max_level' => 999,
        ]);

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['monster_xp_increase' => 1.0]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $service = resolve(CharacterXPService::class)->setCharacter($character->refresh());

        $xp = $service->fetchXpForMonster($monster);

        $this->assertSame(0, $xp);
        $this->assertInstanceOf(RuntimeException::class, $service->xpCalculationFailure());
    }

    public function test_checkpointed_xp_below_the_next_level_requirement_only_adds_xp(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 1, 'xp' => 0, 'xp_next' => 100, 'xp_penalty' => 0]);

        $service = resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(40);

        $character = $character->refresh();

        $this->assertSame(1, $character->level);
        $this->assertSame(40, $character->xp);
        $this->assertSame([], $service->checkpointedProgression());
    }

    public function test_checkpointed_xp_that_exactly_meets_the_requirement_levels_up_once_with_no_left_over_xp(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 1, 'xp' => 0, 'xp_next' => 100, 'xp_penalty' => 0]);

        $service = resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(100);

        $this->assertSame([['level' => 2, 'xp' => 0, 'xp_next' => 100]], $service->checkpointedProgression());
        $this->assertSame(2, $character->refresh()->level);
    }

    public function test_checkpointed_xp_overflow_applies_every_level_up_and_records_one_snapshot_per_level(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 1, 'xp' => 0, 'xp_next' => 100, 'xp_penalty' => 0]);

        $service = resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(350);

        $character = $character->refresh();

        $this->assertSame([
            ['level' => 2, 'xp' => 250, 'xp_next' => 100],
            ['level' => 3, 'xp' => 150, 'xp_next' => 100],
            ['level' => 4, 'xp' => 50, 'xp_next' => 100],
        ], $service->checkpointedProgression());
        $this->assertSame(4, $character->level);
        $this->assertSame(50, $character->xp);
    }

    public function test_checkpointed_xp_invokes_the_checkpoint_callback_once_with_the_final_character_and_progression(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 1, 'xp' => 0, 'xp_next' => 100, 'xp_penalty' => 0]);
        $callbackCalls = [];

        resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(
            250,
            function (int $appliedXp, int $levelsAwarded, Character $checkpointCharacter, array $progression) use (&$callbackCalls): void {
                $callbackCalls[] = [$appliedXp, $levelsAwarded, $checkpointCharacter->level, $checkpointCharacter->xp, $progression];
            },
        );

        $this->assertSame([[
            250,
            2,
            3,
            50,
            [
                ['level' => 2, 'xp' => 150, 'xp_next' => 100],
                ['level' => 3, 'xp' => 50, 'xp_next' => 100],
            ],
        ]], $callbackCalls);
    }

    public function test_checkpointed_xp_for_thousands_of_levels_writes_the_character_once_and_stops_at_the_configured_max_level(): void
    {
        Queue::fake();
        $this->createMaxLevelConfiguration(['max_level' => 5000, 'half_way' => 2500, 'three_quarters' => 3750, 'last_leg' => 4900]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'quest', 'effect' => ItemEffectType::CONTINUE_LEVELING->value]))
            ->getCharacter();
        $character->update(['level' => 1, 'xp' => 0, 'xp_next' => 100, 'xp_penalty' => 0]);

        DB::enableQueryLog();

        $service = resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(1_000_000_000);

        $characterUpdateCount = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_starts_with($query['query'], 'update `characters`'))
            ->count();

        $character = $character->refresh();

        $this->assertSame(1, $characterUpdateCount);
        $this->assertSame(5000, $character->level);
        $this->assertSame(0, $character->xp);
        $this->assertCount(4999, $service->checkpointedProgression());
    }

    public function test_checkpointed_xp_for_a_character_already_at_max_level_normalizes_xp_and_levels_up_nothing(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 1000, 'xp' => 50, 'xp_next' => 100, 'xp_penalty' => 0]);
        $callbackCalls = [];

        $service = resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(
            500,
            function (int $appliedXp, int $levelsAwarded, Character $checkpointCharacter, array $progression) use (&$callbackCalls): void {
                $callbackCalls[] = [$appliedXp, $levelsAwarded, $progression];
            },
        );

        $character = $character->refresh();

        $this->assertSame(1000, $character->level);
        $this->assertSame(0, $character->xp);
        $this->assertSame([], $service->checkpointedProgression());
        $this->assertSame([[500, 0, []]], $callbackCalls);
    }

    public function test_checkpointed_xp_with_an_additional_level_boon_records_one_snapshot_per_trigger(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 1, 'xp' => 0, 'xp_next' => 100, 'xp_penalty' => 0]);
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'gains_additional_level' => true])->id,
            'last_for_minutes' => 60,
            'amount_used' => 2,
            'started' => now(),
            'complete' => now()->addHour(),
        ]);

        $service = resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(250);

        $this->assertSame([
            ['level' => 4, 'xp' => 150, 'xp_next' => 100],
            ['level' => 7, 'xp' => 50, 'xp_next' => 100],
        ], $service->checkpointedProgression());
        $this->assertSame(7, $character->refresh()->level);
    }

    public function test_checkpointed_xp_raises_the_damage_stat_by_two_and_other_core_stats_by_one_per_level(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update([
            'level' => 1,
            'xp' => 0,
            'xp_next' => 100,
            'xp_penalty' => 0,
            'damage_stat' => 'dex',
            'str' => 10,
            'dex' => 10,
        ]);

        resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(350);

        $character = $character->refresh();

        $this->assertSame(16, $character->dex);
        $this->assertSame(13, $character->str);
    }

    public function test_checkpointed_xp_matches_the_per_level_path_when_maxed_stats_grow_stat_modifiers(): void
    {
        Event::fake();
        Queue::fake();
        $bulkCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $perLevelCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $startingState = [
            'level' => 1,
            'xp' => 0,
            'xp_next' => 100,
            'xp_penalty' => 0,
            'damage_stat' => 'dex',
            'str' => MaxReincarnationStats::MAX_STATS,
            'dex' => MaxReincarnationStats::MAX_STATS,
            'base_stat_mod' => 0,
            'base_damage_stat_mod' => 0,
        ];
        $bulkCharacter->update($startingState);
        $perLevelCharacter->update($startingState);

        resolve(CharacterXPService::class)->setCharacter($bulkCharacter->refresh())->distributeCheckpointedXp(350);
        resolve(CharacterXPService::class)->setCharacter($perLevelCharacter->refresh())->distributeSpecifiedXp(350);

        $comparedAttributes = ['level', 'xp', 'xp_next', 'str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus', 'base_stat_mod', 'base_damage_stat_mod'];

        $this->assertSame(
            $perLevelCharacter->refresh()->only($comparedAttributes),
            $bulkCharacter->refresh()->only($comparedAttributes),
        );
    }

    public function test_checkpointed_xp_caps_stat_modifiers_at_their_maximums(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update([
            'level' => 1,
            'xp' => 0,
            'xp_next' => 100,
            'xp_penalty' => 0,
            'damage_stat' => 'dex',
            'str' => MaxReincarnationStats::MAX_STATS,
            'dex' => MaxReincarnationStats::MAX_STATS,
            'base_stat_mod' => 0.5999,
            'base_damage_stat_mod' => 0.4999,
        ]);

        resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(350);

        $character = $character->refresh();

        $this->assertSame(0.60, $character->base_stat_mod);
        $this->assertSame(0.50, $character->base_damage_stat_mod);
    }

    public function test_checkpointed_xp_above_level_one_thousand_matches_the_per_level_penalised_next_level_requirement(): void
    {
        Event::fake();
        Queue::fake();
        $this->createMaxLevelConfiguration(['max_level' => 5000, 'half_way' => 2500, 'three_quarters' => 3750, 'last_leg' => 4900]);
        $bulkCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'quest', 'effect' => ItemEffectType::CONTINUE_LEVELING->value]))
            ->getCharacter();
        $perLevelCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'quest', 'effect' => ItemEffectType::CONTINUE_LEVELING->value]))
            ->getCharacter();
        $startingState = ['level' => 1500, 'xp' => 0, 'xp_next' => 1500, 'xp_penalty' => 0.05];
        $bulkCharacter->update($startingState);
        $perLevelCharacter->update($startingState);

        resolve(CharacterXPService::class)->setCharacter($bulkCharacter->refresh())->distributeCheckpointedXp(1510);
        resolve(CharacterXPService::class)->setCharacter($perLevelCharacter->refresh())->distributeSpecifiedXp(1510);

        $this->assertSame(
            $perLevelCharacter->refresh()->only(['level', 'xp', 'xp_next']),
            $bulkCharacter->refresh()->only(['level', 'xp', 'xp_next']),
        );
    }

    public function test_checkpointed_multi_level_xp_rebuilds_the_attack_cache_once(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 1, 'xp' => 0, 'xp_next' => 100, 'xp_penalty' => 0]);

        resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(550);

        Queue::assertPushed(CharacterAttackTypesCacheBuilder::class, 1);
    }

    public function test_checkpointed_xp_rolls_back_the_character_when_the_checkpoint_callback_fails(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 1, 'xp' => 0, 'xp_next' => 100, 'xp_penalty' => 0]);

        try {
            resolve(CharacterXPService::class)->setCharacter($character->refresh())->distributeCheckpointedXp(
                350,
                function (): void {
                    throw new RuntimeException('checkpoint write failed');
                },
            );
        } catch (RuntimeException $exception) {
            $this->assertSame('checkpoint write failed', $exception->getMessage());
        }

        $character = $character->refresh();

        $this->assertSame(1, $character->level);
        $this->assertSame(0, $character->xp);
        Queue::assertNotPushed(CharacterAttackTypesCacheBuilder::class);
    }
}
