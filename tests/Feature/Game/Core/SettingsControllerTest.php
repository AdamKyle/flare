<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanSeeSettingsPage() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('user.settings', [
            'user' => $user,
        ])->see('Account Settings');
    }

    public function testUpdateEmailSettings() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('user.settings', [
            'user' => $user,
        ])->see('Account Settings')->submitForm('Update Email Settings', [
            'upgraded_building_email' => false,
            'rebuilt_building_email'  => false,
            'kingdom_attack_email'    => false,
        ]);

        $user = $user->refresh();

        $this->assertFalse($user->upgraded_building_email);
        $this->assertFalse($user->rebuilt_building_email);
        $this->assertFalse($user->kingdom_attack_email);
    }

    public function testChatSettings() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('user.settings', [
            'user' => $user,
        ])->see('Account Settings')->submitForm('Update Chat Settings', [
            'show_unit_recruitment_messages' => true,
            'show_building_upgrade_messages' => true,
            'show_kingdom_update_messages'   => false,
            'show_building_rebuilt_messages' => false,
        ]);

        $user = $user->refresh();

        $this->assertTrue($user->show_unit_recruitment_messages);
        $this->assertTrue($user->show_building_upgrade_messages);
        $this->assertFalse($user->show_kingdom_update_messages);
        $this->assertFalse($user->show_building_rebuilt_messages);
    }

    public function testChangeName() {
        $user = $this->character->getUser();

        $oldName = $this->character->getCharacter(false)->name;

        $this->actingAs($user)->visitRoute('user.settings', [
            'user' => $user,
        ])->see('Account Settings')->submitForm('Change Name', [
            'name' => 'BananaCharacter'
        ]);

        $user = $user->refresh();

        $this->assertNotEquals($oldName, $user->character->name);
    }

    public function testEnableDisenchanting() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('user.settings', [
            'user' => $user,
        ])->see('Account Settings')->submitForm('Update Auto Disenchant Settings', [
            'auto_disenchant'        => true,
            'auto_disenchant_amount' => 'all',
        ]);

        $user = $user->refresh();

        $this->assertTrue($user->auto_disenchant);
        $this->assertEquals('all', $user->auto_disenchant_amount);
    }

    public function testFailToEnableDisenchanting() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('user.settings', [
            'user' => $user,
        ])->see('Account Settings')->submitForm('Update Auto Disenchant Settings', [
            'auto_disenchant'        => true,
        ])->see('You must select to either disenchant all items that drop or only those under 1 Billion gold.');

        $user = $user->refresh();

        $this->assertFalse($user->auto_disenchant);
        $this->assertNull($user->auto_disenchant_amount);
    }
}
