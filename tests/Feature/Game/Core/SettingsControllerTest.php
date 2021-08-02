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

        $this->character = (new CharacterFactory)->createBaseCharacter();
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
            'adventure_email'         => true,
            'new_building_email'      => true,
            'kingdoms_update_email'   => false,
            'upgraded_building_email' => false,
            'rebuilt_building_email'  => false,
            'kingdom_attack_email'    => false,
            'unit_recruitment_email'  => false,
        ]);

        $user = $user->refresh();

        $this->assertTrue($user->adventure_email);
        $this->assertTrue($user->new_building_email);
        $this->assertFalse($user->kingdoms_update_email);
        $this->assertFalse($user->upgraded_building_email);
        $this->assertFalse($user->rebuilt_building_email);
        $this->assertFalse($user->kingdom_attack_email);
        $this->assertFalse($user->unit_recruitment_email);
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

        $oldName = $this->character->getCharacter()->name;

        $this->actingAs($user)->visitRoute('user.settings', [
            'user' => $user,
        ])->see('Account Settings')->submitForm('Change Name', [
            'name' => 'BananaCharacter'
        ]);

        $user = $user->refresh();

        $this->assertNotEquals($oldName, $user->character->name);
    }
}
