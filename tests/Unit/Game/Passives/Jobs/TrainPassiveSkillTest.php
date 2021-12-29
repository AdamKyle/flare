<?php

namespace Tests\Unit\Game\Passives\Jobs;

use App\Flare\Models\PassiveSkill;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Setup\Character\CharacterFactory;

class TrainPassiveSkillTest extends TestCase {
    use RefreshDatabase, CreateCharacterBoon;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->assignPassiveSkills();
    }

    public function testLevelPassive() {
        $passive = $this->character->getCharacter(false)->passiveSkills()->first();

        $passive->update([
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        TrainPassiveSkill::dispatch($this->character->getCharacter(false), $passive);

        $passive = $passive->refresh();

        $this->assertTrue($passive->current_level === 1);
    }

    public function testTimeNeeded() {
        $passive = $this->character->getCharacter(false)->passiveSkills()->first();

        $passive->update([
            'started_at' => now(),
            'completed_at' => now()->seconds(10),
        ]);

        TrainPassiveSkill::dispatch($this->character->getCharacter(false), $passive);

        $passive = $passive->refresh();

        $this->assertTrue($passive->current_level === 1);
    }

    public function testNothingHappens() {
        $passive = $this->character->getCharacter(false)->passiveSkills()->first();

        TrainPassiveSkill::dispatch($this->character->getCharacter(false), $passive);

        $passive = $passive->refresh();

        $this->assertTrue($passive->current_level !== 1);
    }

    public function testLevelPassiveAndUnlocksChild() {
        $passive = $this->character->getCharacter(false)->passiveSkills()->whereHas('children')->first();

        $passive->update([
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        TrainPassiveSkill::dispatch($this->character->getCharacter(false), $passive);

        $passive = $passive->refresh();

        $this->assertTrue($passive->current_level === 1);
        $this->assertFalse($passive->children()->first()->is_locked);
    }

    public function testLevelPassiveAndUnlockBuilding() {
        $character = (new CharacterFactory())
                                   ->createBaseCharacter()
                                   ->givePlayerLocation()
                                   ->kingdomManagement()
                                   ->assignKingdom()
                                   ->assignBuilding([
                                        'is_locked'        => true,
                                        'passive_skill_id' => $this->character->getCharacter(false)->passiveSkills()->first()->passiveSkill->id,
                                        'level_required'   => 1,
                                   ], [
                                       'is_locked' => true,
                                   ]);

        $building = $character->getCharacter()->kingdoms->first()->buildings()->first();

        $character = $character->getCharacterFactory()
                              ->assignPassiveSkills($building->gameBuilding)
                              ->getCharacter(false);

        $gamePassive = PassiveSkill::where('name', $building->name)->first();
        $passive     = $character->passiveSkills()->where('passive_skill_id', $gamePassive->id)->first();

        $passive->update([
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        TrainPassiveSkill::dispatch($character, $passive);

        $passive = $passive->refresh();

        $this->assertTrue($passive->current_level === 1);

        $kingdom = $character->refresh()->kingdoms()->first();

        $this->assertNull($kingdom->buildings()->where('is_locked', true)->first());
    }

}
