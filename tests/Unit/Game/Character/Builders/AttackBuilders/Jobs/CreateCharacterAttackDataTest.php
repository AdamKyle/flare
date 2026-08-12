<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\Jobs;

use App\Game\Character\Builders\AttackBuilders\Jobs\CreateCharacterAttackData;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\Exceptions\MissingInventoryException;
use Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Log;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CreateCharacterAttackDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $this->useMockForAttackDataCache = false;

        parent::setUp();
    }

    public function test_handle_builds_the_attack_cache_for_the_character(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->equipBasicAttackLoadout()->getCharacter();

        Cache::forget('character-attack-data-'.$character->id);

        CreateCharacterAttackData::dispatch($character->id);

        $this->assertNotNull(Cache::get('character-attack-data-'.$character->id));
    }

    public function test_handle_returns_early_when_character_does_not_exist(): void
    {
        Log::spy();

        CreateCharacterAttackData::dispatch(999999999);

        Log::shouldNotHaveReceived('warning');

        $this->addToAssertionCount(1);
    }

    public function test_handle_flags_user_for_deletion_when_inventory_is_missing(): void
    {
        Log::spy();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->inventory()->delete();

        CreateCharacterAttackData::dispatch($character->id);

        $this->assertTrue($character->user->refresh()->will_be_deleted);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Character attack data job stopped for a character with missing inventory.', Mockery::on(
                fn (array $context): bool => $context['character_id'] === $character->id
            ));
    }

    public function test_handle_flags_user_for_deletion_when_missing_inventory_exception_is_thrown(): void
    {
        Log::spy();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $buildCharacterAttackTypes = Mockery::mock(BuildCharacterAttackTypes::class);
        $buildCharacterAttackTypes->shouldReceive('buildCache')->andThrow(new MissingInventoryException('The character inventory is missing.'));
        $this->instance(BuildCharacterAttackTypes::class, $buildCharacterAttackTypes);

        CreateCharacterAttackData::dispatch($character->id);

        $this->assertTrue($character->user->refresh()->will_be_deleted);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Character attack data job stopped for a character with missing inventory.', Mockery::on(
                fn (array $context): bool => $context['character_id'] === $character->id
            ));
    }
}
