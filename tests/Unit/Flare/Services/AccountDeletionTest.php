<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Models\AlchemyBag;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\GemBag;
use App\Flare\Models\GemBagSlot;
use App\Flare\Services\CharacterDeletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;

class AccountDeletionTest extends TestCase
{
    use CreateGem, CreateItem, CreateNpc, RefreshDatabase;

    private ?CharacterFactory $characterFactory;

    private ?CharacterDeletion $characterDeletion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();

        $this->characterDeletion = resolve(CharacterDeletion::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
        $this->characterDeletion = null;
    }

    public function test_alchemy_bag_and_slots_are_deleted_with_character(): void
    {
        $character = $this->characterFactory->getCharacter();

        $alchemyBagId = $character->alchemyBag->id;

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $alchemyBagId,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 3,
        ]);

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNull(AlchemyBag::find($alchemyBagId));
        $this->assertEquals(0, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBagId)->count());
    }

    public function test_gem_bag_and_slots_are_deleted_with_character(): void
    {
        $character = $this->characterFactory->getCharacter();

        $gemBagId = $character->gemBag->id;

        GemBagSlot::create([
            'gem_bag_id' => $gemBagId,
            'gem_id' => $this->createGem()->id,
            'amount' => 2,
        ]);

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNull(GemBag::find($gemBagId));
        $this->assertEquals(0, GemBagSlot::where('gem_bag_id', $gemBagId)->count());
    }

    public function test_deletion_succeeds_when_alchemy_bag_does_not_exist(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->alchemyBag()->delete();

        $characterId = $character->id;

        $this->characterDeletion->deleteCharacterFromUser($character->refresh());

        $this->assertNull(AlchemyBag::where('character_id', $characterId)->first());
    }

    public function test_deletion_succeeds_when_gem_bag_does_not_exist(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->gemBag()->delete();

        $characterId = $character->id;

        $this->characterDeletion->deleteCharacterFromUser($character->refresh());

        $this->assertNull(GemBag::where('character_id', $characterId)->first());
    }
}
