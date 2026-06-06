<?php

namespace Tests\Console;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ClearExpiredCharacterBoonsTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function testItDeletesExpiredBoonsAndClearsAffectedCharacterCaches(): void
    {
        $firstCharacter = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $secondCharacter = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $unaffectedCharacter = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $item = $this->createItem();

        $firstCharacter->boons()->create([
            'character_id' => $firstCharacter->id,
            'item_id' => $item->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now()->subMinutes(20),
            'complete' => now()->subMinutes(10),
        ]);

        $firstCharacter->boons()->create([
            'character_id' => $firstCharacter->id,
            'item_id' => $item->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now()->subMinutes(10),
            'complete' => now(),
        ]);

        $activeBoon = $firstCharacter->boons()->create([
            'character_id' => $firstCharacter->id,
            'item_id' => $item->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(10),
        ]);

        $secondCharacter->boons()->create([
            'character_id' => $secondCharacter->id,
            'item_id' => $item->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now()->subMinutes(20),
            'complete' => now()->subMinutes(10),
        ]);

        $unaffectedBoon = $unaffectedCharacter->boons()->create([
            'character_id' => $unaffectedCharacter->id,
            'item_id' => $item->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(10),
        ]);

        Cache::put('can-character-survive-' . $firstCharacter->id, true);
        Cache::put('can-character-survive-' . $secondCharacter->id, true);
        Cache::put('can-character-survive-' . $unaffectedCharacter->id, true);

        $buildCharacterAttackTypes = Mockery::mock(BuildCharacterAttackTypes::class);
        $buildCharacterAttackTypes->shouldReceive('buildCache')
            ->twice()
            ->withArgs(function (Character $character) use ($firstCharacter, $secondCharacter): bool {
                return in_array($character->id, [$firstCharacter->id, $secondCharacter->id], true);
            })
            ->andReturn([]);

        $this->app->instance(BuildCharacterAttackTypes::class, $buildCharacterAttackTypes);

        $this->assertEquals(0, $this->artisan('clear:expired-character-boons'));

        $this->assertSame(2, CharacterBoon::query()->count());
        $this->assertTrue(CharacterBoon::query()->whereKey($activeBoon->id)->exists());
        $this->assertTrue(CharacterBoon::query()->whereKey($unaffectedBoon->id)->exists());
        $this->assertFalse(Cache::has('can-character-survive-' . $firstCharacter->id));
        $this->assertFalse(Cache::has('can-character-survive-' . $secondCharacter->id));
        $this->assertTrue(Cache::has('can-character-survive-' . $unaffectedCharacter->id));
    }
}
