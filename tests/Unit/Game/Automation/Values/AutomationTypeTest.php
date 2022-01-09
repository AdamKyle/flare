<?php

namespace Tests\Unit\Game\Automation\Values;

use App\Game\Automation\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationTypeTest extends TestCase
{
    use RefreshDatabase;

    public function testIsAttackType() {
        $this->assertTrue((new AutomationType(0))->isAttack());
    }

    public function testThrowsError() {
        $this->expectException(\Exception::class);

        new AutomationType(847565);
    }
}
