<?php

namespace Tests\Unit\Admin\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Admin\Jobs\LevelTestCharacter;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;

class LevelTestCharacterTest extends TestCase
{
    use RefreshDatabase;

    public function testLevelTestCharacter()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->giveSnapShot()->getCharacter();

        LevelTestCharacter::dispatch($character, 1);
        
        $this->assertTrue($character->snapshots->isNotEmpty());
        $this->assertNotempty($character->snapShots->first()->snap_shot);
    }

    
}
