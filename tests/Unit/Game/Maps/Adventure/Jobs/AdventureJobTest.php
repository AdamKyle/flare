<?php

namespace Tests\Unit\Game\Maps\Adventure\Jobs;

use Cache;
use Str;
use App\Game\Adventures\Jobs\AdventureJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class AdventureJobTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateItemAffix, CreateItem;

    public function setUp(): void {
        parent::setUp();
    }

    public function testAdventureJob()
    {
        $this->createItemAffix();
        $this->createItem();

        $adventure = $this->createNewAdventure();

        $character = (new CharacterFactory)->createBaseCharacter()
                                         ->createAdventureLog($adventure)
                                         ->givePlayerLocation()
                                         ->getCharacter();

        Event::fake();

        $jobName = Str::random(80);

        Cache::put('character_'.$character->id.'_adventure_'.$adventure->id, $jobName, now()->addMinutes(5));

        for ($i = 1; $i <= $adventure->levels; $i++) {
            AdventureJob::dispatch($character, $adventure, $jobName, $i);

            $character = $character->refresh();

            $this->assertTrue(!empty($character->adventureLogs->first()->logs));

        }

    }

    public function testAdventureJobDoesNotExecuteWhenNameDoesntMatch()
    {
        $adventure = $this->createNewAdventure();

        $character = (new CharacterFactory)->createBaseCharacter()
                                         ->createAdventureLog($adventure)
                                         ->getCharacter();

        Event::fake();

        $jobName = Str::random(80);

        Cache::put('character_'.$character->id.'_adventure_'.$adventure->id, 'sample', now()->addMinutes(5));

        for ($i = 1; $i <= $adventure->levels; $i++) {
            AdventureJob::dispatch($character, $adventure, $jobName, $i);

            $character->refresh();

            $this->assertTrue(empty($character->adventureLogs->first()->logs));
        }
    }
}
