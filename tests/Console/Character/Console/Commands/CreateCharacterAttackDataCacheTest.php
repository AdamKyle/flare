<?php

namespace Tests\Console\Character\Console\Commands;

use App\Game\Character\Builders\AttackBuilders\Jobs\CreateCharacterAttackData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CreateCharacterAttackDataCacheTest extends TestCase
{
    use RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_dispatches_attack_data_job_for_a_specific_character(): void
    {
        Queue::fake();

        $character = $this->character->getCharacter();

        Artisan::call('create:character-attack-data', ['characterId' => $character->id]);

        Queue::assertPushed(CreateCharacterAttackData::class, function (CreateCharacterAttackData $job) use ($character): bool {
            return $job->characterId === $character->id;
        });
    }

    public function test_dispatches_attack_data_job_for_all_characters_when_no_id_given(): void
    {
        Queue::fake();

        $character = $this->character->getCharacter();

        Artisan::call('create:character-attack-data');

        Queue::assertPushed(CreateCharacterAttackData::class, function (CreateCharacterAttackData $job) use ($character): bool {
            return $job->characterId === $character->id;
        });
    }

    public function test_does_nothing_extra_when_specific_character_id_does_not_exist(): void
    {
        Queue::fake();

        Artisan::call('create:character-attack-data', ['characterId' => 999999]);

        Queue::assertPushed(CreateCharacterAttackData::class, 1);
    }
}
