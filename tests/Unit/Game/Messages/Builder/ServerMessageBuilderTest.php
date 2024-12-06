<?php

namespace Tests\Unit\Game\Messages\Builder;

use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Types\AdminMessageTypes;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\ChatMessageTypes;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use App\Game\Messages\Types\KingdomMessageTypes;
use App\Game\Messages\Types\LotteryMessageType;
use App\Game\Messages\Types\MapMessageTypes;
use App\Game\Messages\Types\MovementMessageTypes;
use Tests\TestCase;

class ServerMessageBuilderTest extends TestCase
{
    private ?ServerMessageBuilder $serverMessageBuilder;

    public function setUp(): void
    {
        parent::setUp();

        $this->serverMessageBuilder = new ServerMessageBuilder;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->serverMessageBuilder = null;
    }

    public function testMessageLengthZero()
    {
        $message = $this->serverMessageBuilder->build(ChatMessageTypes::INVALID_MESSAGE_LENGTH);

        $this->assertEquals('Your message cannot be empty.', $message);
    }

    public function testInventoryFull()
    {
        $message = $this->serverMessageBuilder->build(CharacterMessageTypes::INVENTORY_IS_FULL);

        $this->assertEquals('Your inventory is full, you cannot pick up this item!', $message);
    }

    public function testCannotMove()
    {
        $message = $this->serverMessageBuilder->build(MapMessageTypes::CANT_MOVE);

        $this->assertEquals('Please wait for the timer (beside movement options) to state: Ready!', $message);
    }

    public function testCannotMoveInEitherDirection()
    {
        $right = $this->serverMessageBuilder->build(MapMessageTypes::CANNOT_MOVE_RIGHT);
        $left = $this->serverMessageBuilder->build(MapMessageTypes::CANNOT_MOVE_LEFT);
        $down = $this->serverMessageBuilder->build(MapMessageTypes::CANNOT_MOVE_DOWN);
        $up = $this->serverMessageBuilder->build(MapMessageTypes::CANNOT_MOVE_UP);

        $this->assertEquals('You cannot go that way.', $right);
        $this->assertEquals('You cannot go that way.', $left);
        $this->assertEquals('You cannot go that way.', $down);
        $this->assertEquals('You cannot go that way.', $up);
    }

    public function testNotEnoughGold()
    {
        $message = $this->serverMessageBuilder->build(CharacterMessageTypes::NOT_ENOUGH_GOLD);

        $this->assertEquals('You don\'t have enough Gold for that.', $message);
    }

    public function testNotEnoughGoldDust()
    {
        $message = $this->serverMessageBuilder->build(CharacterMessageTypes::NOT_ENOUGH_GOLD_DUST);

        $this->assertEquals('You don\'t have enough Gold Dust for that.', $message);
    }

    public function testNotEnoughShards()
    {
        $message = $this->serverMessageBuilder->build(CharacterMessageTypes::NOT_ENOUGH_SHARDS);

        $this->assertEquals('You don\'t have enough Shards for that.', $message);
    }

    public function testToHardToCraft()
    {
        $message = $this->serverMessageBuilder->build(CraftingMessageTypes::TO_HARD_TO_CRAFT);

        $this->assertEquals('You are too low level and thus, you lost your investment and epically failed to craft this item!', $message);
    }

    public function testToEasyToCraft()
    {
        $message = $this->serverMessageBuilder->build(CraftingMessageTypes::TO_EASY_TO_CRAFT);

        $this->assertEquals('This is far too easy to craft! You will get no experience for this item.', $message);
    }

    public function testChattingTooMuch()
    {
        $message = $this->serverMessageBuilder->build(ChatMessageTypes::CHATTING_TO_MUCH);

        $this->assertEquals('You can only chat so much in a one minute window. Slow down!', $message);
    }

    public function testGoldCapped()
    {
        $message = $this->serverMessageBuilder->build(CurrenciesMessageTypes::GOLD_CAPPED);

        $this->assertEquals('You are gold capped! Max gold a character can hold is two trillion. If you have kingdoms try depositing some of it or buying gold bars or maybe spend some of it?', $message);
    }

    public function testFailedToCraft()
    {
        $message = $this->serverMessageBuilder->build(CraftingMessageTypes::FAILED_TO_CRAFT);

        $this->assertEquals('You failed to craft the item! You lost the investment.', $message);
    }

    public function testFailedToDisenchant()
    {
        $message = $this->serverMessageBuilder->build(CraftingMessageTypes::FAILED_TO_DISENCHANT);

        $this->assertEquals('Failed to disenchant the item, it shatters before you into ashes. You only got 1 Gold Dust for your efforts.', $message);
    }

    public function testLevelUpCharacter()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CharacterMessageTypes::LEVEL_UP, 1);

        $this->assertEquals('You are now level: 1!', $message);
    }

    public function testGoldRush()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CurrenciesMessageTypes::GOLD_RUSH, 1);

        $this->assertEquals('Gold Rush! Your gold is now: 1 Gold! 5% of your total gold has been awarded to you.', $message);
    }

    public function testCrafted()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CraftingMessageTypes::CRAFTED, 'Test');

        $this->assertEquals('You crafted a: Test!', $message);
    }

    public function testNewDamageStat()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CharacterMessageTypes::NEW_DAMAGE_STAT, 'Test');

        $this->assertEquals('The Creator has changed your classes damage stat to: Test. Please adjust your gear accordingly for maximum damage.', $message);
    }

    public function testDisenchanted()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CraftingMessageTypes::DISENCHANTED, 'Test');

        $this->assertEquals('Disenchanted the item and got: Test Gold Dust.', $message);
    }

    public function testLottoMax()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(LotteryMessageType::LOTTO_MAX, 'Test');

        $this->assertEquals('You won the daily Gold Dust Lottery! Congrats! You won: Test Gold Dust', $message);
    }

    public function testDailyLotto()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(LotteryMessageType::DAILY_LOTTERY, 'Test');

        $this->assertEquals('You got: Test Gold Dust from the daily lottery', $message);
    }

    public function testTransmuted()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CraftingMessageTypes::TRANSMUTED, 'Test');

        $this->assertEquals('You transmuted a new: Test It shines with a powerful glow!', $message);
    }

    public function testEnchantmentFailed()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CraftingMessageTypes::ENCHANTMENT_FAILED, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testSilenced()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CharacterMessageTypes::SILENCED, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testDeletedAffix()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(AdminMessageTypes::DELETED_AFFIX, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testBuildingRepaired()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(KingdomMessageTypes::BUILDING_REPAIR_FINISHED, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testBuildingUpgraded()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(KingdomMessageTypes::BUILDING_UPGRADE_FINISHED, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testSoldItem()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CharacterMessageTypes::SOLD_ITEM_ON_MARKET, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testNewBuilding()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(KingdomMessageTypes::NEW_BUILDING, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testKingdomResourcesUpdated()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(KingdomMessageTypes::KINGDOM_RESOUCE_UPDATE, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testUnitRecruitmentFinished()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(KingdomMessageTypes::UNIT_RECRUITMENT_FINISHED, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testPlaneTransfer()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(MovementMessageTypes::PLANE_TRANSFER, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testEnchanted()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(CraftingMessageTypes::ENCHANTED, 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testMovedLocation()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation(MovementMessageTypes::MOVE_LOCATION, 'Test');

        $this->assertEquals('Test', $message);
    }
}
