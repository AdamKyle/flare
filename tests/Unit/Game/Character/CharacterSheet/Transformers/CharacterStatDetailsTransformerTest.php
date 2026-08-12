<?php

namespace Tests\Unit\Game\Character\CharacterSheet\Transformers;

use App\Game\Character\CharacterSheet\Transformers\CharacterStatDetailsTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterStatDetailsTransformerTest extends TestCase
{
    use RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterStatDetailsTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
        $this->transformer = resolve(CharacterStatDetailsTransformer::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->transformer = null;
    }

    public function test_transform_includes_the_characters_base_stats(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertSame($character->str, $data['str']);
        $this->assertSame($character->dur, $data['dur']);
        $this->assertSame($character->dex, $data['dex']);
        $this->assertSame($character->chr, $data['chr']);
        $this->assertSame($character->int, $data['int']);
        $this->assertSame($character->agi, $data['agi']);
        $this->assertSame($character->focus, $data['focus']);
    }

    public function test_transform_includes_the_built_combat_stats(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertArrayHasKey('health', $data);
        $this->assertArrayHasKey('voided_health', $data);
        $this->assertArrayHasKey('ac', $data);
        $this->assertArrayHasKey('voided_ac', $data);
        $this->assertArrayHasKey('weapon_attack', $data);
        $this->assertArrayHasKey('ring_damage', $data);
        $this->assertArrayHasKey('spell_damage', $data);
        $this->assertArrayHasKey('holy_bonus', $data);
    }

    public function test_set_ignore_reductions_is_respected_by_transform(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $this->transformer->setIgnoreReductions(true);

        $data = $this->transformer->transform($character);

        $this->assertArrayHasKey('ac', $data);
    }
}
