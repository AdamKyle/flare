<?php

namespace Tests\Unit\Game\Core\Jobs;

use App\Flare\Values\ItemUsabilityType;
use App\Game\Core\Jobs\CharacterBoonJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Setup\Character\CharacterFactory;

class CharacterBoonJobTest extends TestCase {
    use RefreshDatabase, CreateCharacterBoon;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    public function testDeleteBoon() {
        $character = $this->character->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'stat_bonus'   => 0.08,
            'started'      => now(),
            'complete'     => now()->subHour(10),
            'type'         => ItemUsabilityType::STAT_INCREASE
        ]);

        $character = $character->refresh();

        CharacterBoonJob::dispatch($boon->id);

        $this->assertCount(0, $character->boons->toArray());
    }

    public function testDoNotDeleteBoon() {
        $character = $this->character->getCharacter();

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'stat_bonus'   => 0.08,
            'started'      => now(),
            'complete'     => now()->subHour(10),
            'type'         => ItemUsabilityType::STAT_INCREASE
        ]);

        $character = $character->refresh();

        CharacterBoonJob::dispatch(rand(900,1200));

        $this->assertCount(1, $character->boons->toArray());
    }

}
