<?php

namespace Tests\Unit\Game\Messages\Builder;

use App\Game\Messages\Builders\ServerMessageBuilder;
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
        $message = $this->serverMessageBuilder->build('message_length_0');

        $this->assertEquals('Your message cannot be empty.', $message);
    }

    public function testMessageToMax()
    {
        $message = $this->serverMessageBuilder->build('message_to_max');

        $this->assertEquals('Your message is too long.', $message);
    }

    public function testInvalidCommand()
    {
        $message = $this->serverMessageBuilder->build('invalid_command');

        $this->assertEquals('Command not recognized.', $message);
    }

    public function testNoUserFound()
    {
        $message = $this->serverMessageBuilder->build('no_matching_user');

        $this->assertEquals('Could not find a user with that name to private message.', $message);
    }

    public function testNoMonsterFound()
    {
        $message = $this->serverMessageBuilder->build('no_monster');

        $this->assertEquals('No monster selected. Please select one.', $message);
    }

    public function testDeadCharacter()
    {
        $message = $this->serverMessageBuilder->build('dead_character');

        $this->assertEquals('You are dead. Please revive yourself by clicking revive.', $message);
    }

    public function testInventoryFull()
    {
        $message = $this->serverMessageBuilder->build('inventory_full');

        $this->assertEquals('Your inventory is full, you cannot pick up this item!', $message);
    }

    public function testCannotAttack()
    {
        $message = $this->serverMessageBuilder->build('cant_attack');

        $this->assertEquals('Please wait for the timer (beside Again!) to state: Ready!', $message);
    }

    public function testCannotMove()
    {
        $message = $this->serverMessageBuilder->build('cant_move');

        $this->assertEquals('Please wait for the timer (beside movement options) to state: Ready!', $message);
    }

    public function testCannotEnterLocation()
    {
        $message = $this->serverMessageBuilder->build('cannot_enter_location');

        $this->assertEquals('You are too busy to enter this location. (Are you auto battling? If so, stop. Then enter - then begin again)', $message);
    }

    public function testCannotMoveInEitherDirection()
    {
        $right = $this->serverMessageBuilder->build('cannot_move_right');
        $left = $this->serverMessageBuilder->build('cannot_move_left');
        $down = $this->serverMessageBuilder->build('cannot_move_down');
        $up = $this->serverMessageBuilder->build('cannot_move_up');

        $this->assertEquals('You cannot go that way.', $right);
        $this->assertEquals('You cannot go that way.', $left);
        $this->assertEquals('You cannot go that way.', $down);
        $this->assertEquals('You cannot go that way.', $up);
    }

    public function testCannotWalkOnWater()
    {
        $message = $this->serverMessageBuilder->build('cannot_walk_on_water');

        $this->assertEquals('You cannot move that way, you are missing the appropriate quest item.', $message);
    }

    public function testNotEnoughGold()
    {
        $message = $this->serverMessageBuilder->build('not_enough_gold');

        $this->assertEquals('You don\'t have enough Gold for that.', $message);
    }

    public function testNotEnoughGoldDust()
    {
        $message = $this->serverMessageBuilder->build('not_enough_gold_dust');

        $this->assertEquals('You don\'t have enough Gold Dust for that.', $message);
    }

    public function testNotEnoughShards()
    {
        $message = $this->serverMessageBuilder->build('not_enough_shards');

        $this->assertEquals('You don\'t have enough Shards for that.', $message);
    }

    public function testCannotCraft()
    {
        $message = $this->serverMessageBuilder->build('cant_craft');

        $this->assertEquals('You must wait for the timer (beside Craft/Enchant) to state: Ready!', $message);
    }

    public function testCannotEnchant()
    {
        $message = $this->serverMessageBuilder->build('cant_enchant');

        $this->assertEquals('You must wait for the timer (beside Craft/Enchant) to state: Ready!', $message);
    }

    public function testCannotUseSmithyBench()
    {
        $message = $this->serverMessageBuilder->build('cant_use_smithy_bench');

        $this->assertEquals('No, child! You are busy. Wait for the timer to finish.', $message);
    }

    public function testToHardToCraft()
    {
        $message = $this->serverMessageBuilder->build('to_hard_to_craft');

        $this->assertEquals('You are too low level and thus, you lost your investment and epically failed to craft this item!', $message);
    }

    public function testToEasyToCraft()
    {
        $message = $this->serverMessageBuilder->build('to_easy_to_craft');

        $this->assertEquals('This is far too easy to craft! You will get no experience for this item.', $message);
    }

    public function testSomethingWentWrong()
    {
        $message = $this->serverMessageBuilder->build('something_went_wrong');

        $this->assertEquals('A component was unable to render. Please try refreshing the page.', $message);
    }

    public function testAttackingTooMuch()
    {
        $message = $this->serverMessageBuilder->build('attacking_to_much');

        $this->assertEquals('You are attacking too much in a one minute window.', $message);
    }

    public function testChattingTooMuch()
    {
        $message = $this->serverMessageBuilder->build('chatting_to_much');

        $this->assertEquals('You can only chat so much in a one minute window. Slow down!', $message);
    }

    public function testMessageLengthMax()
    {
        $message = $this->serverMessageBuilder->build('message_length_max');

        $this->assertEquals('Your message is far too long.', $message);
    }

    public function testNoMatchingCommand()
    {
        $message = $this->serverMessageBuilder->build('no_matching_command');

        $this->assertEquals('The NPC does not understand you. Their eyes blink in confusion.', $message);
    }

    public function testGoldCapped()
    {
        $message = $this->serverMessageBuilder->build('gold_capped');

        $this->assertEquals('Gold Rush! You are now gold capped!', $message);
    }

    public function testFailedToCraft()
    {
        $message = $this->serverMessageBuilder->build('failed_to_craft');

        $this->assertEquals('You failed to craft the item! You lost the investment.', $message);
    }

    public function testFailedToDisenchant()
    {
        $message = $this->serverMessageBuilder->build('failed_to_disenchant');

        $this->assertEquals('Failed to disenchant the item, it shatters before you into ashes. You only got 1 Gold Dust for your efforts.', $message);
    }

    public function testFailedToTransmute()
    {
        $message = $this->serverMessageBuilder->build('failed_to_transmute');

        $this->assertEquals('You failed to transmute the item. It melts into a pool of liquid gold dust before evaporating away. Wasted efforts!', $message);
    }

    public function testWrongType()
    {
        $message = $this->serverMessageBuilder->build('some-command');

        $this->assertEquals('', $message);
    }

    public function testLevelUpCharacter()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('level_up', 1);

        $this->assertEquals('You are now level: 1!', $message);
    }

    public function testGoldRush()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('gold_rush', 1);

        $this->assertEquals('Gold Rush! Your gold is now: 1 Gold! 5% of your total gold has been awarded to you.', $message);
    }

    public function testCrafted()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('crafted', 'Test');

        $this->assertEquals('You crafted a: Test!', $message);
    }

    public function testNewDamageStat()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('new_damage_stat', 'Test');

        $this->assertEquals('The Creator has changed your classes damage stat to: Test. Please adjust your gear accordingly for maximum damage.', $message);
    }

    public function testDisenchanted()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('disenchanted', 'Test');

        $this->assertEquals('Disenchanted the item and got: Test Gold Dust.', $message);
    }

    public function testLottoMax()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('lotto_max', 'Test');

        $this->assertEquals('You won the daily Gold Dust Lottery! Congrats! You won: Test Gold Dust', $message);
    }

    public function testDailyLotto()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('daily_lottery', 'Test');

        $this->assertEquals('You got: Test Gold Dust from the daily lottery', $message);
    }

    public function testTransmuted()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('transmuted', 'Test');

        $this->assertEquals('You transmuted a new: Test It shines with a powerful glow!', $message);
    }

    public function testEnchantmentFailed()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('enchantment_failed', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testSilenced()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('silenced', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testDeletedAffix()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('deleted_affix', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testBuildingRepaired()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('building_repair_finished', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testBuildingUpgraded()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('building_upgrade_finished', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testSoldItem()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('sold_item', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testNewBuilding()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('new_building', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testKingdomResourcesUpdated()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('kingdom_resources_update', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testUnitRecruitmentFinished()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('unit_recruitment_finished', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testPlaneTransfer()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('plane_transfer', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testEnchanted()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('enchanted', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testMovedLocation()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('moved_location', 'Test');

        $this->assertEquals('Test', $message);
    }

    public function testBuildDefaultMessageWhenTypesIsNotInAdditionalInformation()
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation('cant_attack');

        $this->assertEquals('Please wait for the timer (beside Again!) to state: Ready!', $message);
    }
}
