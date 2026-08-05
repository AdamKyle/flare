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
use Tests\Traits\CreateGameSkill;

class GemCraftingControllerTest extends TestCase
{
    use CreateGameSkill, RefreshDatabase;

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
    }
}
