<?php

namespace Tests\Unit\Game\Maps\Jobs;

use App\Game\Adventures\Jobs\AdventureJob;
use Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Jobs\MoveTimeOutJob;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class AdventureJobTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateGameSkill, CreateItem, CreateItemAffix;


    public function testAdventureJob()
    {

        $this->createItem();
        $this->createItemAffix();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->levelCharacterUp(5)->getCharacter(false);

        $adventure = $this->createNewAdventure();

        $character->adventureLogs()->create([
            'character_id'         => $character->id,
            'adventure_id'         => $adventure->id,
            'complete'             => false,
            'in_progress'          => true,
            'took_to_long'         => false,
            'last_completed_level' => null,
            'logs'                 => null,
            'rewards'              => null,
            'created_at'           => null,
        ]);

        Cache::put('character_'.$character->id.'_adventure_'.$adventure->id, 'Sample');

        AdventureJob::dispatch($character->refresh(), $adventure, 'attack', 'Sample', 1);

        $this->assertTrue($character->refresh()->adventureLogs()->first()->complete);
    }

    public function testBailWhenJobNameDoesNotExist()
    {

        $this->createItem();
        $this->createItemAffix();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->levelCharacterUp(5)->getCharacter(false);

        $adventure = $this->createNewAdventure();

        $character->adventureLogs()->create([
            'character_id'         => $character->id,
            'adventure_id'         => $adventure->id,
            'complete'             => false,
            'in_progress'          => true,
            'took_to_long'         => false,
            'last_completed_level' => null,
            'logs'                 => null,
            'rewards'              => null,
            'created_at'           => null,
        ]);

        AdventureJob::dispatch($character->refresh(), $adventure, 'attack', 'Sample', 1);

        $this->assertFalse($character->refresh()->adventureLogs()->first()->complete);
    }


}
