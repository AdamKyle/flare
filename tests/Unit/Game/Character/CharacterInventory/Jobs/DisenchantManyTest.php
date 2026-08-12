<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Jobs;

use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class DisenchantManyTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

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

    public function test_handle_disenchants_items_and_awards_gold_dust_on_pass(): void
    {
        Event::fake();

        $item = $this->createItem(['type' => 'weapon']);
        $baseSkill = $this->createGameSkill(['type' => SkillTypeValue::DISENCHANTING, 'skill_bonus_per_level' => 2.0, 'max_level' => 10]);
        $character = $this->character
            ->assignSkill($baseSkill, 5)
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        DisenchantMany::dispatch($character, [$item->id]);

        $this->assertGreaterThan(0, $character->fresh()->gold_dust);
    }

    public function test_handle_processes_items_when_gold_dust_is_already_capped(): void
    {
        Event::fake();

        $item = $this->createItem(['type' => 'weapon']);
        $baseSkill = $this->createGameSkill(['type' => SkillTypeValue::DISENCHANTING, 'skill_bonus_per_level' => 2.0, 'max_level' => 10]);
        $character = $this->character
            ->assignSkill($baseSkill, 5)
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);

        DisenchantMany::dispatch($character->fresh(), [$item->id]);

        $this->assertSame(CurrencyLimit::MAX_GOLD_DUST, $character->fresh()->gold_dust);
    }
}
