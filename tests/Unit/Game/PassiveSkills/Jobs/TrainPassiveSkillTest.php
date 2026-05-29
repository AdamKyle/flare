<?php

namespace Tests\Unit\Game\PassiveSkills\Jobs;

use App\Flare\Models\Character;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreatePassiveSkill;

class TrainPassiveSkillTest extends TestCase
{
    use CreatePassiveSkill, RefreshDatabase;

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

    public function test_passive_unlocks_kingdom_building()
    {
        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'name' => 'Goblin Coin Bank',
            ], [
                'is_locked' => true,
            ])
            ->getCharacter();

        $passive = $character->passiveSkills()->create([
            'character_id' => $character->id,
            'passive_skill_id' => $this->createPassiveSkill(array_merge([
                'effect_type' => PassiveSkillTypeValue::UNLOCKS_BUILDING,
            ], [
                'name' => $character->kingdoms->first()->buildings->first()->name,
            ]))->id,
            'parent_skill_id' => null,
            'current_level' => 0,
            'hours_to_next' => 1,
            'is_locked' => false,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->subMinute(),
        ]);

        TrainPassiveSkill::dispatch($character, $passive);

        $character = $character->refresh();
        $building = $character->kingdoms->first()->buildings->first();

        $this->assertFalse($building->is_locked);
    }

    public function test_passive_training_recalculates_all_owned_kingdom_caps()
    {
        $character = (new CharacterFactory)
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
            ->getCharacter();

        $passiveSkill = $this->createPassiveSkill([
            'effect_type' => PassiveSkillTypeValue::RESOURCE_INCREASE,
            'resource_bonus_per_level' => 10,
            'max_level' => 5,
        ]);
        $passive = $character->passiveSkills()->create([
            'character_id' => $character->id,
            'passive_skill_id' => $passiveSkill->id,
            'parent_skill_id' => null,
            'current_level' => 0,
            'hours_to_next' => 1,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->subMinute(),
            'is_locked' => false,
        ]);

        TrainPassiveSkill::dispatch($character, $passive);

        $kingdom = $character->refresh()->kingdoms->first();

        $this->assertSame(2010, $kingdom->max_stone);
        $this->assertSame(2010, $kingdom->max_wood);
        $this->assertSame(2010, $kingdom->max_clay);
        $this->assertSame(2010, $kingdom->max_iron);
        $this->assertSame(110, $kingdom->max_population);
    }
}
