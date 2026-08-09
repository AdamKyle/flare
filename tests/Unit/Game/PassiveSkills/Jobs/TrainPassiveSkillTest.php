<?php

namespace Tests\Unit\Game\PassiveSkills\Jobs;

use App\Flare\Models\Character;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class TrainPassiveSkillTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_do_not_level_passive()
    {
        $passive = $this->character->passiveSkills()->first();

        TrainPassiveSkill::dispatch($this->character, $passive);

        $passive = $passive->refresh();

        $this->assertEquals(0, $passive->current_level);
    }

    public function test_level_up_passive()
    {
        $passive = $this->character->passiveSkills()->first();

        $passive->update([
            'started_at' => now()->subMinute(),
            'completed_at' => now()->subMinute(),
        ]);

        $passive = $passive->refresh();

        TrainPassiveSkill::dispatch($this->character, $passive);

        $passive = $passive->refresh();

        $this->assertEquals(1, $passive->current_level);
    }

    public function test_do_not_over_level_passive()
    {
        $passive = $this->character->passiveSkills()->first();

        $passive->update([
            'current_level' => 5,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->subMinute(),
        ]);

        $passive = $passive->refresh();

        TrainPassiveSkill::dispatch($this->character, $passive);

        $passive = $passive->refresh();

        $this->assertEquals(5, $passive->current_level);
    }

    public function test_leveling_to_max_level_sets_hours_to_next_to_zero()
    {
        $passive = $this->character->passiveSkills()->first();

        $passive->passiveSkill()->update(['max_level' => 1]);

        $passive->update([
            'current_level' => 0,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->subMinute(),
        ]);

        $passive = $passive->refresh();

        TrainPassiveSkill::dispatch($this->character, $passive);

        $passive = $passive->refresh();

        $this->assertSame(1, $passive->current_level);
        $this->assertSame(0, $passive->hours_to_next);
    }

    public function test_passive_unlocks_kingdom_building()
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'name' => 'Goblin Coin Bank',
            ], [
                'is_locked' => true,
            ])
            ->getCharacterFactory();

        $buildingName = $characterFactory->getCharacter()->kingdoms->first()->buildings->first()->name;

        $characterFactory = $characterFactory->passiveSkillManagement()->assignPassiveSkill(
            PassiveSkillTypeValue::UNLOCKS_BUILDING,
            0,
            ['name' => $buildingName],
            [
                'hours_to_next' => 1,
                'started_at' => now()->subMinute(),
                'completed_at' => now()->subMinute(),
            ],
        )->getCharacterFactory();

        $character = $characterFactory->getCharacter();
        $passive = $character->passiveSkills()->latest('id')->first();

        TrainPassiveSkill::dispatch($character, $passive);

        $character = $character->refresh();
        $building = $character->kingdoms->first()->buildings->first();

        $this->assertFalse($building->is_locked);
    }

    public function test_passive_training_recalculates_all_owned_kingdom_caps()
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom([
                'max_stone' => 2000,
                'max_wood' => 2000,
                'max_clay' => 2000,
                'max_iron' => 2000,
                'max_population' => 100,
                'current_population' => 100,
            ])
            ->getCharacterFactory();

        $characterFactory = $characterFactory->passiveSkillManagement()->assignPassiveSkill(
            PassiveSkillTypeValue::RESOURCE_INCREASE,
            0,
            [
                'resource_bonus_per_level' => 10,
                'max_level' => 5,
            ],
            [
                'hours_to_next' => 1,
                'started_at' => now()->subMinute(),
                'completed_at' => now()->subMinute(),
            ],
        )->getCharacterFactory();

        $character = $characterFactory->getCharacter();
        $passive = $character->passiveSkills()->latest('id')->first();

        TrainPassiveSkill::dispatch($character, $passive);

        $kingdom = $character->refresh()->kingdoms->first();

        $this->assertSame(2010, $kingdom->max_stone);
        $this->assertSame(2010, $kingdom->max_wood);
        $this->assertSame(2010, $kingdom->max_clay);
        $this->assertSame(2010, $kingdom->max_iron);
        $this->assertSame(110, $kingdom->max_population);
    }

    public function test_passive_training_increases_max_steel_for_owned_kingdoms()
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom([
                'max_steel' => 500,
            ])
            ->getCharacterFactory();

        $characterFactory = $characterFactory->passiveSkillManagement()->assignPassiveSkill(
            PassiveSkillTypeValue::STEEL_INCREASE,
            0,
            [
                'resource_bonus_per_level' => 25,
                'max_level' => 5,
            ],
            [
                'hours_to_next' => 1,
                'started_at' => now()->subMinute(),
                'completed_at' => now()->subMinute(),
            ],
        )->getCharacterFactory();

        $character = $characterFactory->getCharacter();
        $passive = $character->passiveSkills()->latest('id')->first();

        TrainPassiveSkill::dispatch($character, $passive);

        $kingdom = $character->refresh()->kingdoms->first();

        $this->assertSame(525, $kingdom->max_steel);
    }
}
