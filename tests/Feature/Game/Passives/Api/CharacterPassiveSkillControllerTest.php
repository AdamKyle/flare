<?php

namespace Tests\Feature\Game\Passives\Api;

use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;
use Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterPassiveSkillControllerTest extends TestCase
{
    use RefreshDatabase;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()
                                                   ->givePlayerLocation()
                                                   ->getCharacter(false);
    }

    public function testTrainPassiveSkill() {

        Queue::fake();

        $response = $this->actingAs($this->character->user)->json('post', route('train.passive.skill', [
            'characterPassiveSkill' => $this->character->passiveSkills()->first()->id,
            'character'             => $this->character->id,
        ]))->response;

        Queue::assertPushed(TrainPassiveSkill::class);

        $this->assertEquals(200, $response->status());
    }

    public function testStopTrainingPassiveSkill() {
        $passive = $this->character->passiveSkills()->first();

        $passive->update([
            'started_at'   => now(),
            'completed_at' => now()->addHours(3),
        ]);

        $passive = $passive->refresh();

        $response = $this->actingAs($this->character->user)->json('post', route('stop.training.passive.skill', [
            'characterPassiveSkill' => $passive->id,
            'character'             => $this->character->id,
        ]))->response;

        $this->assertEquals(200, $response->status());

        $passive = $passive->refresh();

        $this->assertNull($passive->started_at);
        $this->assertNull($passive->completed_at);
    }


}