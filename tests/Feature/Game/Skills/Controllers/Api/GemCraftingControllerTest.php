<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Skills\Services\GemService;
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

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $craftingSkill = $this->createGameSkill([
            'name' => 'Gem Crafting',
            'type' => SkillTypeValue::GEM_CRAFTING->value,
            'max_level' => 100,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->assignSkill(
                $craftingSkill,
                10
            )
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_craftable_gems()
    {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/gem-crafting/craftable-tiers/'.$this->character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['tiers']);
        $this->assertEquals(0, $jsonData['skill_xp']['current_xp']);
    }

    public function test_craft_gem()
    {

        $this->character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $this->instance(
            GemService::class,
            Mockery::mock(GemService::class, [resolve(GemBuilder::class)], function (MockInterface $mock) {
                $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
            })
        );

        $character = $this->character->refresh();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/gem-crafting/craft/'.$this->character->id, [
                'tier' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $character->refresh();

        $this->assertNotEmpty($jsonData['tiers']);
        $this->assertGreaterThan(0, $jsonData['skill_xp']['current_xp']);
        $this->assertLessThan(MaxCurrenciesValue::MAX_COPPER, $character->copper_coins);
        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
        $this->assertNotEmpty($character->gemBag->gemSlots);
    }
}
