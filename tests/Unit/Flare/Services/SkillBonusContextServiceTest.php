<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Flare\Services\SkillBonusContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class SkillBonusContextServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private CharacterFactory $characterFactory;

    private Character $character;

    private Skill $skill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory())->createBaseCharacter();

        $this->character = $this->characterFactory->getCharacter();

        $this->skill = Skill::query()
            ->where('character_id', $this->character->id)
            ->with('baseSkill')
            ->firstOrFail();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_get_equipped_slots_with_items_uses_loaded_relations_and_caches_by_character_id(): void
    {
        $equippedItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.inventory.slots.item'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slotsFirstCall = $service->getEquippedSlotsWithItems();
        $slotsSecondCall = $service->getEquippedSlotsWithItems();

        $this->assertCount(1, $slotsFirstCall);
        $this->assertSame($slotsFirstCall, $slotsSecondCall);
        $this->assertTrue($slotsFirstCall->first()->relationLoaded('item'));
        $this->assertEquals($equippedItem->id, $slotsFirstCall->first()->item->id);
    }

    public function test_get_equipped_slots_with_items_falls_back_to_inventory_slot_query_when_loaded_relations_missing_pieces(): void
    {
        $equippedItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getEquippedSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($equippedItem->id, $slots->first()->item->id);
    }

    public function test_get_equipped_slots_with_items_uses_equipped_inventory_set_when_inventory_has_no_equipped_slots(): void
    {
        $setEquippedItem = $this->createItem();

        $this->characterFactory->inventorySetManagement()
            ->createInventorySets(1)
            ->putItemInSet($setEquippedItem, 0, 'right-hand', true);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getEquippedSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($setEquippedItem->id, $slots->first()->item->id);
    }

    public function test_get_equipped_slots_with_items_returns_empty_when_no_inventory_record_exists(): void
    {
        $this->character->inventory()->delete();

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slotsFirstCall = $service->getEquippedSlotsWithItems();
        $slotsSecondCall = $service->getEquippedSlotsWithItems();

        $this->assertTrue($slotsFirstCall->isEmpty());
        $this->assertSame($slotsFirstCall, $slotsSecondCall);
    }

    public function test_get_quest_slots_with_items_uses_loaded_relations_and_caches_by_inventory_id_and_skill_name(): void
    {
        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $questItem = $this->createItem([
            'type' => 'quest',
            'skill_name' => $skill->baseSkill->name,
        ]);

        $otherItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $questItem->id,
            'equipped' => false,
            'position' => 'slot-1',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $otherItem->id,
            'equipped' => false,
            'position' => 'slot-2',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.inventory.slots.item'])
            ->firstOrFail();

        $skill->character->inventory->slots->first(function ($slot) use ($otherItem) {
            return ! is_null($slot->item) && $slot->item->id === $otherItem->id;
        })->setRelation('item', null);

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slotsFirstCall = $service->getQuestSlotsWithItems();
        $slotsSecondCall = $service->getQuestSlotsWithItems();

        $this->assertCount(1, $slotsFirstCall);
        $this->assertSame($slotsFirstCall, $slotsSecondCall);
        $this->assertTrue($slotsFirstCall->first()->relationLoaded('item'));
        $this->assertEquals($questItem->id, $slotsFirstCall->first()->item->id);
    }

    public function test_get_quest_slots_with_items_falls_back_to_query_when_items_are_not_loaded(): void
    {
        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $questItem = $this->createItem([
            'type' => 'quest',
            'skill_name' => $skill->baseSkill->name,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $questItem->id,
            'equipped' => false,
            'position' => 'slot-1',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.inventory.slots'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getQuestSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($questItem->id, $slots->first()->item->id);
    }

    public function test_get_quest_slots_with_items_returns_empty_when_no_inventory_record_exists(): void
    {
        $this->character->inventory()->delete();

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getQuestSlotsWithItems();

        $this->assertTrue($slots->isEmpty());
    }

    public function test_get_boons_with_item_used_returns_empty_when_character_relation_is_null(): void
    {
        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $skill->setRelation('character', null);

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $boons = $service->getBoonsWithItemUsed();

        $this->assertTrue($boons->isEmpty());
    }

    public function test_get_boons_with_item_used_uses_loaded_relations_and_caches(): void
    {
        $itemOne = $this->createItem();
        $itemTwo = $this->createItem();

        $this->character->boons()->create([
            'character_id' => $this->character->id,
            'item_id' => $itemOne->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(10),
        ]);

        $this->character->boons()->create([
            'character_id' => $this->character->id,
            'item_id' => $itemTwo->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(10),
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.boons.itemUsed'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $boonsFirstCall = $service->getBoonsWithItemUsed();
        $boonsSecondCall = $service->getBoonsWithItemUsed();

        $this->assertCount(2, $boonsFirstCall);
        $this->assertSame($boonsFirstCall, $boonsSecondCall);

        $this->assertTrue($boonsFirstCall->every(function ($boon) {
            return $boon->relationLoaded('itemUsed') && ! is_null($boon->itemUsed);
        }));
    }

    public function test_get_boons_with_item_used_falls_back_to_query_when_boons_loaded_but_item_used_not_loaded(): void
    {
        $item = $this->createItem();

        $this->character->boons()->create([
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(10),
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.boons'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $boons = $service->getBoonsWithItemUsed();

        $this->assertCount(1, $boons);
        $this->assertTrue($boons->first()->relationLoaded('itemUsed'));
        $this->assertEquals($item->id, $boons->first()->itemUsed->id);
    }

    public function test_get_boons_with_item_used_falls_back_to_query_when_boons_not_loaded(): void
    {
        $item = $this->createItem();

        $this->character->boons()->create([
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(10),
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $boons = $service->getBoonsWithItemUsed();

        $this->assertCount(1, $boons);
        $this->assertTrue($boons->first()->relationLoaded('itemUsed'));
        $this->assertEquals($item->id, $boons->first()->itemUsed->id);
    }

    public function test_get_game_class_uses_loaded_relation_and_caches(): void
    {
        $character = $this->character->load('class');

        $service = resolve(SkillBonusContextService::class);

        $classFirstCall = $service->getGameClass($character);
        $classSecondCall = $service->getGameClass($character);

        $this->assertNotNull($classFirstCall);
        $this->assertSame($classFirstCall, $classSecondCall);
        $this->assertEquals($character->game_class_id, $classFirstCall->id);
    }

    public function test_get_game_class_falls_back_to_query_when_relation_not_loaded(): void
    {
        $character = $this->character->refresh();

        $service = resolve(SkillBonusContextService::class);

        $class = $service->getGameClass($character);

        $this->assertNotNull($class);
        $this->assertEquals($character->game_class_id, $class->id);
    }

    public function test_get_equipped_slots_with_items_falls_back_when_skill_character_is_loaded_but_null(): void
    {
        $equippedItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character'])
            ->firstOrFail();

        $skill->setRelation('character', null);

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getEquippedSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($equippedItem->id, $slots->first()->item->id);
    }

    public function test_get_equipped_slots_with_items_falls_back_when_character_inventory_is_not_loaded(): void
    {
        $equippedItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getEquippedSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($equippedItem->id, $slots->first()->item->id);
    }

    public function test_get_equipped_slots_with_items_falls_back_when_character_inventory_is_loaded_but_null(): void
    {
        $equippedItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.inventory'])
            ->firstOrFail();

        $skill->character->setRelation('inventory', null);

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getEquippedSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($equippedItem->id, $slots->first()->item->id);
    }

    public function test_get_equipped_slots_with_items_falls_back_when_inventory_slots_are_not_loaded(): void
    {
        $equippedItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.inventory'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getEquippedSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($equippedItem->id, $slots->first()->item->id);
    }

    public function test_get_equipped_slots_with_items_falls_back_when_equipped_slots_are_empty_in_loaded_relations(): void
    {
        $nonEquippedItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $nonEquippedItem->id,
            'equipped' => false,
            'position' => 'slot-1',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.inventory.slots.item'])
            ->firstOrFail();

        $equippedItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getEquippedSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($equippedItem->id, $slots->first()->item->id);
    }

    public function test_get_equipped_slots_with_items_falls_back_when_equipped_slot_items_are_not_loaded(): void
    {
        $equippedItem = $this->createItem();

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.inventory.slots'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getEquippedSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($equippedItem->id, $slots->first()->item->id);
    }

    public function test_get_quest_slots_with_items_falls_back_when_skill_character_is_loaded_but_null(): void
    {
        $skillForName = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $questItem = $this->createItem([
            'type' => 'quest',
            'skill_name' => $skillForName->baseSkill->name,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $questItem->id,
            'equipped' => false,
            'position' => 'slot-1',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character'])
            ->firstOrFail();

        $skill->setRelation('character', null);

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getQuestSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($questItem->id, $slots->first()->item->id);
    }

    public function test_get_quest_slots_with_items_falls_back_when_character_inventory_is_not_loaded(): void
    {
        $skillForName = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $questItem = $this->createItem([
            'type' => 'quest',
            'skill_name' => $skillForName->baseSkill->name,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $questItem->id,
            'equipped' => false,
            'position' => 'slot-1',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getQuestSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($questItem->id, $slots->first()->item->id);
    }

    public function test_get_quest_slots_with_items_falls_back_when_character_inventory_is_loaded_but_null(): void
    {
        $skillForName = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $questItem = $this->createItem([
            'type' => 'quest',
            'skill_name' => $skillForName->baseSkill->name,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $questItem->id,
            'equipped' => false,
            'position' => 'slot-1',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.inventory'])
            ->firstOrFail();

        $skill->character->setRelation('inventory', null);

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getQuestSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($questItem->id, $slots->first()->item->id);
    }

    public function test_get_quest_slots_with_items_falls_back_when_inventory_slots_are_not_loaded(): void
    {
        $skillForName = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill'])
            ->firstOrFail();

        $questItem = $this->createItem([
            'type' => 'quest',
            'skill_name' => $skillForName->baseSkill->name,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $questItem->id,
            'equipped' => false,
            'position' => 'slot-1',
        ]);

        $skill = Skill::query()
            ->whereKey($this->skill->id)
            ->with(['baseSkill', 'character.inventory'])
            ->firstOrFail();

        $service = resolve(SkillBonusContextService::class);

        $service->setSkillInstance($skill);

        $slots = $service->getQuestSlotsWithItems();

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->relationLoaded('item'));
        $this->assertEquals($questItem->id, $slots->first()->item->id);
    }
}
