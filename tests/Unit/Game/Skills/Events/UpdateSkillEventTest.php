<?php

namespace Tests\Unit\Game\Skills\Events;

use App\Game\Skills\Events\UpdateSkillEvent;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateSkill;

class UpdateSkillEventTest extends TestCase
{
    use CreateGameMap, CreateGameSkill, CreateSkill;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_do_not_update_skill()
    {
        $gameSkill = $this->createGameSkill();
        $character = $this->character->getCharacter();

        $skill = $this->createSkill([
            'character_id' => $character->id,
            'game_skill_id' => $gameSkill->id,
            'level' => $gameSkill->max_level,
            'xp' => 999,
        ]);

        Event(new UpdateSkillEvent($skill));

        $skill = $skill->refresh();

        $this->assertEquals($gameSkill->max_level, $skill->level);
        $this->assertEquals(0, $skill->xp);
    }

    public function test_update_skill()
    {
        $gameSkill = $this->createGameSkill();
        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'skill_training_bonus' => 2.0,
        ]);

        $character->map()->create([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        $skill = $this->createSkill([
            'character_id' => $character->id,
            'game_skill_id' => $gameSkill->id,
            'level' => 1,
            'xp' => 9999,
        ]);

        Event(new UpdateSkillEvent($skill));

        $skill = $skill->refresh();

        $this->assertEquals($gameSkill->max_level, $skill->level);
        $this->assertEquals(0, $skill->xp);
    }

    public function test_update_skill_and_update_character_attack_data()
    {
        $gameSkill = $this->createGameSkill([
            'base_damage_mod_bonus_per_level' => 0.10,
        ]);
        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'skill_training_bonus' => 2.0,
        ]);

        $character->map()->create([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        $skill = $this->createSkill([
            'character_id' => $character->id,
            'game_skill_id' => $gameSkill->id,
            'level' => 1,
            'xp' => 9999,
        ]);

        Event(new UpdateSkillEvent($skill));

        $skill = $skill->refresh();

        $this->assertEquals($gameSkill->max_level, $skill->level);
        $this->assertEquals(0, $skill->xp);
        $this->assertEquals(0.50, $skill->base_damage_mod);
    }
}
