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

    public function test_weapon_type_is_eligible_for_sockets()
    {
        $this->assertTrue($this->itemSocketEligibility->isEligible('weapon'));
    }

    public function test_trinket_type_is_not_eligible_for_sockets()
    {
        $this->assertFalse($this->itemSocketEligibility->isEligible('trinket'));
    }

    public function test_artifact_type_is_not_eligible_for_sockets()
    {
        $this->assertFalse($this->itemSocketEligibility->isEligible('artifact'));
    }

    public function test_max_socket_count_is_six()
    {
        $this->assertSame(6, $this->itemSocketEligibility->maxSocketCount());
    }
}
