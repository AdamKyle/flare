<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use App\Game\Automation\Values\AutomationType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateItem;

class CharacterInventoryControllerTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateCharacterAutomation, CreateItem, RefreshDatabase;

    public function test_use_item_endpoint_succeeds_for_alchemy_boon_during_automation(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/use-item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Used selected item.', $jsonData['message']);
    }

    public function test_use_item_endpoint_returns_unprocessable_for_non_boon_item_during_automation(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'weapon',
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/use-item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            'No you are busy, you can use Alchemy items that apply boons to your character. Please cancel your: Exploration, if you want to use this.',
            $jsonData['message']
        );
    }
}
