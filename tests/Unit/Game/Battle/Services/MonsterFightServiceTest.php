<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;

class MonsterFightServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character = null;

    private ?Monster $monster = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->character->map->game_map_id,
            ])
            ->getMonster();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        $this->character = null;
        $this->monster = null;

        parent::tearDown();
    }

    public function test_setup_monster_deletes_character_sheet_cache_by_default(): void
    {
        Cache::put('character-sheet-'.$this->character->id, ['level' => 1]);

        $monsterPlayerFight = Mockery::mock(MonsterPlayerFight::class);
        $monsterPlayerFight->shouldReceive('setUpFight')->andReturn([]);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);

        $this->instance(MonsterPlayerFight::class, $monsterPlayerFight);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(WeeklyBattleService::class, $weeklyBattleService);

        $service = resolve(MonsterFightService::class);

        $service->setupMonster($this->character, ['selected_monster_id' => $this->monster->id], true, false, false);

        $this->assertFalse(Cache::has('character-sheet-'.$this->character->id));
    }

    public function test_setup_monster_preserves_character_sheet_cache_when_flag_is_true(): void
    {
        Cache::put('character-sheet-'.$this->character->id, ['level' => 1]);

        $monsterPlayerFight = Mockery::mock(MonsterPlayerFight::class);
        $monsterPlayerFight->shouldReceive('setUpFight')->andReturn([]);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);

        $this->instance(MonsterPlayerFight::class, $monsterPlayerFight);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(WeeklyBattleService::class, $weeklyBattleService);

        $service = resolve(MonsterFightService::class);

        $service->setupMonster($this->character, ['selected_monster_id' => $this->monster->id], true, false, true);

        $this->assertTrue(Cache::has('character-sheet-'.$this->character->id));
    }
}
