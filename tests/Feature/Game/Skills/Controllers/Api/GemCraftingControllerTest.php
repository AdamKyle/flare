<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameGemAbility;
use Tests\Traits\CreateGameSkill;

class GemCraftingControllerTest extends TestCase
{
    use CreateGameGemAbility, CreateGameSkill, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createGameGemAbility();
    }

    public function test_craftable_tiers_endpoint_returns_tiers_and_skill_xp(): void
    {
        $gemSkill = $this->createGameSkill([
            'name' => 'Gem Crafting',
            'type' => SkillTypeValue::GEM_CRAFTING->value,
            'max_level' => 100,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->assignSkill($gemSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/gem-crafting/craftable-tiers/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertNotEmpty($data['tiers']);
        $this->assertArrayHasKey('current_xp', $data['skill_xp']);
    }

    public function test_craft_gem_success_returns_craft_succeeded_and_crafted_gem(): void
    {
        $this->instance(
            ChanceCalculator::class,
            Mockery::mock(ChanceCalculator::class, function (MockInterface $mock) {
                $mock->shouldReceive('passesPercentage')->andReturn(true);
            })
        );

        $gemSkill = $this->createGameSkill([
            'name' => 'Gem Crafting',
            'type' => SkillTypeValue::GEM_CRAFTING->value,
            'max_level' => 100,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->assignSkill($gemSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/gem-crafting/craft/'.$character->id, [
                'tier' => 1,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertTrue($data['craft_succeeded']);
        $this->assertNotNull($data['crafted_gem']);
        $this->assertArrayHasKey('id', $data['crafted_gem']);
        $this->assertArrayHasKey('tier', $data['crafted_gem']);
        $this->assertIsString($data['message']);

        $preview = $data['crafted_gem_preview'];

        $this->assertNotNull($preview);
        $this->assertTrue($character->gemBag->gemSlots()->whereKey($preview['slot_id'])->exists());
        $this->assertSame($data['crafted_gem']['name'], $preview['name']);
        $this->assertSame($data['crafted_gem']['tier'], $preview['tier']);
        $this->assertCount(3, $preview['modifiers']);
        $this->assertSame('gem_ability', $preview['modifiers'][0]['modifier_type']);
        $this->assertArrayNotHasKey('label', $preview['modifiers'][0]);
        $this->assertArrayNotHasKey('display_type', $preview['modifiers'][0]);
        $this->assertNotNull($preview['modifiers'][0]['ability']);
        $this->assertArrayNotHasKey('weak_against', $preview);
        $this->assertArrayNotHasKey('primary_atonement_type', $preview);
    }

    public function test_craft_gem_failure_returns_null_crafted_gem(): void
    {
        $this->instance(
            ChanceCalculator::class,
            Mockery::mock(ChanceCalculator::class, function (MockInterface $mock) {
                $mock->shouldReceive('passesPercentage')->andReturn(false);
            })
        );

        $gemSkill = $this->createGameSkill([
            'name' => 'Gem Crafting',
            'type' => SkillTypeValue::GEM_CRAFTING->value,
            'max_level' => 100,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->assignSkill($gemSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/gem-crafting/craft/'.$character->id, [
                'tier' => 1,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertFalse($data['craft_succeeded']);
        $this->assertNull($data['crafted_gem']);
        $this->assertNull($data['crafted_gem_preview']);
    }

    public function test_craft_gem_cannot_afford_returns_error(): void
    {
        $gemSkill = $this->createGameSkill([
            'name' => 'Gem Crafting',
            'type' => SkillTypeValue::GEM_CRAFTING->value,
            'max_level' => 100,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->assignSkill($gemSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/gem-crafting/craft/'.$character->id, [
                'tier' => 1,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertFalse($data['craft_succeeded']);
        $this->assertNull($data['crafted_gem']);
        $this->assertNull($data['crafted_gem_preview']);
    }
}
