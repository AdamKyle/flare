<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\Jobs;

use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\Exceptions\MissingInventoryException;
use Cache;
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Log;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterAttackTypesCacheBuilderTest extends TestCase
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

        CharacterAttackTypesCacheBuilder::dispatch($character);

        $this->assertNotNull(Cache::get('character-attack-data-'.$character->id));
    }

    public function test_handle_dispatches_automation_log_update_when_alert_stats_updated_is_true(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAttackTypesCacheBuilder::dispatch($character, true);

        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event): bool {
            return $event->message === 'Character stats have been updated.';
        });
    }

    public function test_handle_does_not_dispatch_automation_log_update_when_alert_stats_updated_is_false(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAttackTypesCacheBuilder::dispatch($character, false);

        Event::assertNotDispatched(AutomationLogUpdate::class);
    }

    public function test_handle_flags_user_for_deletion_when_inventory_is_missing(): void
    {
        Log::spy();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->inventory()->delete();

        CharacterAttackTypesCacheBuilder::dispatch($character->refresh());

        $this->assertTrue($character->user->refresh()->will_be_deleted);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Character attack cache job stopped for a character with missing inventory.', Mockery::on(
                fn (array $context): bool => $context['character_id'] === $character->id
            ));
    }

    public function test_handle_flags_user_for_deletion_when_missing_inventory_exception_is_thrown(): void
    {
        Log::spy();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $handler = Mockery::mock(UpdateCharacterAttackTypesHandler::class);
        $handler->shouldReceive('updateCache')->andThrow(new MissingInventoryException('The character inventory is missing.'));
        $this->instance(UpdateCharacterAttackTypesHandler::class, $handler);

        CharacterAttackTypesCacheBuilder::dispatch($character);

        $this->assertTrue($character->user->refresh()->will_be_deleted);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Character attack cache job stopped for a character with missing inventory.', Mockery::on(
                fn (array $context): bool => $context['character_id'] === $character->id
            ));
    }
}
