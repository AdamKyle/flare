<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\CharacterMercenary;
use App\Game\Mercenaries\Services\MercenaryService;
use App\Game\Mercenaries\Values\MercenaryValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class MercenaryServiceTest extends TestCase {

    use RefreshDatabase, CreateItem;

    private ?CharacterFactory $character;

    private ?MercenaryService $mercenaryService;

    public function setUp(): void {
        parent::setUp();

        $this->character        = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->mercenaryService = resolve(MercenaryService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character        = null;
        $this->mercenaryService = null;
    }

    public function testHasMercenary() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 1,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
        ]);

        $character = $character->refresh();

        $mercs = $this->mercenaryService->formatCharacterMercenaries($character->mercenaries);

        $this->assertEquals(1, count($mercs));
        $this->assertEquals('Child of gold dust', $mercs[0]['name']);
    }

    public function testYouAlreadyOwnThisMercenary() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 1,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
        ]);

        $character = $character->refresh();

        $purchaseRequest = $this->mercenaryService->purchaseMercenary([
            'type' => MercenaryValue::CHILD_OF_GOLD_DUST
        ], $character);

        $this->assertEquals(422, $purchaseRequest['status']);
        $this->assertEquals('No. You already have this Mercenary.', $purchaseRequest['message']);
    }

    public function testNotEnoughGoldForThisMercenary() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 0
        ]);

        $character = $character->refresh();

        $purchaseRequest = $this->mercenaryService->purchaseMercenary([
            'type' => MercenaryValue::CHILD_OF_GOLD_DUST
        ], $character);

        $this->assertEquals(422, $purchaseRequest['status']);
        $this->assertEquals('You cannot afford to purchase this mercenary!', $purchaseRequest['message']);
    }

    public function testInvalidTypeForPurchasing() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 10000000
        ]);

        $character = $character->refresh();

        $purchaseRequest = $this->mercenaryService->purchaseMercenary([
            'type' => 10
        ], $character);

        $this->assertEquals(422, $purchaseRequest['status']);
        $this->assertEquals('Invalid type.', $purchaseRequest['message']);
    }

    public function testCannotPurchaseChildOfCopperCoinsWithOutTheQuestitem() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 10000000
        ]);

        $character = $character->refresh();

        $purchaseRequest = $this->mercenaryService->purchaseMercenary([
            'type' => MercenaryValue::CHILD_OF_COPPER_COINS
        ], $character);

        $this->assertEquals(422, $purchaseRequest['status']);
        $this->assertEquals('You need to complete the Quest: The Magic of Purgatory in Hell before being able to purchase this Mercenary.', $purchaseRequest['message']);
    }

    public function testPurchaseMerc() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 10000000
        ]);

        $character = $character->refresh();

        $purchaseRequest = $this->mercenaryService->purchaseMercenary([
            'type' => MercenaryValue::CHILD_OF_GOLD_DUST
        ], $character);

        $character = $character->refresh();

        $this->assertEquals(200, $purchaseRequest['status']);
        $this->assertEquals('Child of gold dust', $purchaseRequest['merc_data'][0]['name']);
        $this->assertTrue(!in_array(MercenaryValue::CHILD_OF_GOLD_DUST, $purchaseRequest['mercs_to_buy']));
        $this->assertEquals(0, $character->gold);
        $this->assertEquals('You purchased: Child of gold dust!', $purchaseRequest['message']);
    }

    public function testFailToReincarnateAnotherPlayersMercenary() {
        $character          = $this->character->getCharacter();
        $secondaryCharacter = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $secondaryCharacter->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 1,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
        ]);

        $result = $this->mercenaryService->reIncarnateMercenary($character, $merc);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Not allowed to do that.', $result['message']);
    }

    public function testCannotReincarnateWhenNotAtMaxlevel() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 1,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
        ]);

        $character = $character->refresh();

        $result = $this->mercenaryService->reIncarnateMercenary($character, $merc);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Mercenary is not at level 100.', $result['message']);
    }

    public function testCannotReincarnateWhenReincarnatedMaxTimes() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 100,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
            'times_reincarnated'   => 10
        ]);

        $character = $character->refresh();

        $result = $this->mercenaryService->reIncarnateMercenary($character, $merc);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot reincarnate any more.', $result['message']);
    }

    public function testCannotReincarnateWhenNoShards() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 100,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
            'times_reincarnated'   => 0
        ]);

        $character = $character->refresh();

        $result = $this->mercenaryService->reIncarnateMercenary($character, $merc);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Not enough shards to reincarnate. Cost is 500 Shards.', $result['message']);
    }

    public function testCanReincarnate() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 100,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
            'times_reincarnated'   => 0
        ]);

        $character->update(['shards' => 1000]);

        $character = $character->refresh();

        $result = $this->mercenaryService->reIncarnateMercenary($character, $merc);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(500, $character->shards);
        $this->assertEquals('Re-incarnated Mercenary!', $result['message']);
        $this->assertEquals(1, $result['merc_data'][0]['times_reincarnated']);
    }

    public function testGainNoXpWhenAtMaxLevel() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 100,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
            'times_reincarnated'   => 0
        ]);

        $this->mercenaryService->giveXpToMercenaries($character->refresh());

        $merc = $merc->refresh();

        $this->assertEquals(0, $merc->current_xp);
    }

    public function testMercLevelsUp() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 1,
            'current_xp'           => 995,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
            'times_reincarnated'   => 0
        ]);

        $this->mercenaryService->giveXpToMercenaries($character->refresh());

        $merc = $merc->refresh();

        $this->assertEquals(0, $merc->current_xp);

        $this->assertEquals(2, $merc->current_level);
    }

    public function testMercGainsXP() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 1,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
            'times_reincarnated'   => 0
        ]);

        $this->mercenaryService->giveXpToMercenaries($character->refresh());

        $merc = $merc->refresh();

        $this->assertEquals(25, $merc->current_xp);

        $this->assertEquals(1, $merc->current_level);
    }
}
