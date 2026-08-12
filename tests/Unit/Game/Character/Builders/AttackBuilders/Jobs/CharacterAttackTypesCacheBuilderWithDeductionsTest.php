<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\Jobs;

use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilderWithDeductions;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Character\Exceptions\MissingInventoryException;
use Cache;
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Log;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterAttackTypesCacheBuilderWithDeductionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $this->useMockForAttackDataCache = false;

        parent::setUp();
    }

    public function test_handle_builds_the_attack_cache_and_dispatches_update_event(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->equipBasicAttackLoadout()->getCharacter();

        Cache::forget('character-attack-data-'.$character->id);

        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character, 0.1);

        $this->assertNotNull(Cache::get('character-attack-data-'.$character->id));
        Event::assertDispatched(UpdateCharacterAttackEvent::class, function (UpdateCharacterAttackEvent $event) use ($character): bool {
            return $event->character->id === $character->id && $event->ignoreReductions === false;
        });
    }

    public function test_handle_flags_user_for_deletion_when_inventory_is_missing(): void
    {
        Log::spy();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->inventory()->delete();

        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character->refresh());

        $this->assertTrue($character->user->refresh()->will_be_deleted);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Character attack cache deduction job stopped for a character with missing inventory.', Mockery::on(
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

        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character);

        $this->assertTrue($character->user->refresh()->will_be_deleted);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Character attack cache deduction job stopped for a character with missing inventory.', Mockery::on(
                fn (array $context): bool => $context['character_id'] === $character->id
            ));
    }
}
