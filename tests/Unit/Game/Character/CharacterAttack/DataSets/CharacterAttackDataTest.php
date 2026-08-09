<?php

namespace Tests\Unit\Game\Character\CharacterAttack\DataSets;

use App\Game\Character\CharacterAttack\DataSets\CharacterAttackData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterAttackDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_attack_types_returns_empty_array_when_not_cached(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        Cache::forget('character-attack-data-'.$character->id);

        $attackTypes = (new CharacterAttackData)->fetchAttackTypes($character);

        $this->assertSame([], $attackTypes);
    }

    public function test_fetch_attack_types_returns_cached_attack_types(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['attack' => 10],
        ]);

        $attackTypes = (new CharacterAttackData)->fetchAttackTypes($character);

        $this->assertSame(['attack' => 10], $attackTypes);
    }
}
