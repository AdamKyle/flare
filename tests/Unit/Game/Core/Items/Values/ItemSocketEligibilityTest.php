<?php

namespace Tests\Unit\Game\Core\Items\Values;

use App\Game\Core\Items\Values\ItemSocketEligibility;
use Tests\TestCase;

class ItemSocketEligibilityTest extends TestCase
{
    private ItemSocketEligibility $itemSocketEligibility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemSocketEligibility = new ItemSocketEligibility;
    }

    public function test_weapon_type_is_eligible_for_sockets(): void
    {
        $this->assertTrue($this->itemSocketEligibility->isEligible('weapon'));
    }

    public function test_trinket_type_is_not_eligible_for_sockets(): void
    {
        $this->assertFalse($this->itemSocketEligibility->isEligible('trinket'));
    }

    public function test_artifact_type_is_not_eligible_for_sockets(): void
    {
        $this->assertFalse($this->itemSocketEligibility->isEligible('artifact'));
    }

    public function test_body_max_socket_count_is_six(): void
    {
        $this->assertSame(6, $this->itemSocketEligibility->maxSocketCount('body'));
    }

    public function test_one_handed_weapon_max_socket_count_is_three(): void
    {
        $this->assertSame(3, $this->itemSocketEligibility->maxSocketCount('sword'));
    }

    public function test_one_socket_armour_is_classified(): void
    {
        $this->assertTrue($this->itemSocketEligibility->isOneSocketArmour('leggings'));
    }

    public function test_two_handed_physical_weapon_is_classified(): void
    {
        $this->assertTrue($this->itemSocketEligibility->isTwoHanded('hammer'));
    }

    public function test_spell_damage_is_never_socketable(): void
    {
        $this->assertFalse($this->itemSocketEligibility->isEligible('spell-damage'));
    }
}
