<?php

namespace Tests\Console;


use App\Game\Character\Builders\AttackBuilders\Jobs\CreateCharacterAttackData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CreateCharacterAttackDataCacheTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateAttackData()
    {

        Queue::fake();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter(false);

        $this->assertEquals(0, $this->artisan('create:character-attack-data'));

        Queue::assertPushed(CreateCharacterAttackData::class);
    }
}
