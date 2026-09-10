<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterActiveBoonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;

class CharacterActiveBoonServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateCharacter, CreateCharacterBoon, CreateItem, CreateUser, RefreshDatabase;

    private ?CharacterActiveBoonService $characterActiveBoonService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterActiveBoonService = resolve(CharacterActiveBoonService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterActiveBoonService = null;
    }

    public function test_active_boons_returns_the_presentation_ready_source_item_and_alchemy_bag_amount_left(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $item = $this->createItem([
            'name' => 'Boon Bottle',
            'type' => 'alchemy',
            'usable' => true,
            'lasts_for' => 30,
        ]);

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 3,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 1,
            'last_for_minutes' => 30,
        ]);

        $rows = $this->characterActiveBoonService->activeBoons($character->refresh());

        $this->assertCount(1, $rows);
        $this->assertSame($boon->id, $rows[0]['id']);
        $this->assertSame($item->id, $rows[0]['item_id']);
        $this->assertSame($item->id, $rows[0]['boon_applied']['item_id']);
        $this->assertSame('Boon Bottle', $rows[0]['boon_applied']['name']);
        $this->assertSame(3, $rows[0]['amount_left']);
    }

    public function test_active_boons_returns_zero_amount_left_when_the_character_has_no_alchemy_bag(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);

        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'lasts_for' => 30,
        ]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 1,
            'last_for_minutes' => 30,
        ]);

        $rows = $this->characterActiveBoonService->activeBoons($character->refresh());

        $this->assertCount(1, $rows);
        $this->assertSame(0, $rows[0]['amount_left']);
    }

    public function test_active_boons_excludes_expired_boon_rows(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'lasts_for' => 30,
        ]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now()->subHour(),
            'complete' => now()->subMinutes(5),
            'amount_used' => 1,
            'last_for_minutes' => 30,
        ]);

        $rows = $this->characterActiveBoonService->activeBoons($character->refresh());

        $this->assertSame([], $rows);
    }
}
