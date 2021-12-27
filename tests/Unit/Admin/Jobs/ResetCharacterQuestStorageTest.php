<?php

namespace Tests\Unit\Admin\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Admin\Jobs\ResetCharacterQuestStorage;
use App\Game\Core\Events\ResetQuestStorageBroadcastEvent;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateUser;

class ResetCharacterQuestStorageTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateKingdom, CreateGameBuilding, CreateGameUnit;

    public function testResetCharacterQuestStorage()
    {
        Event::fake();

        (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        ResetCharacterQuestStorage::dispatch();

        Event::assertDispatched(ResetQuestStorageBroadcastEvent::class);
    }


}
