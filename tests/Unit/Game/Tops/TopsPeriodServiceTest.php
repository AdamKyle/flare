<?php

namespace Tests\Unit\Game\Tops;

use App\Game\Tops\Services\TopsPeriodService;
use Tests\TestCase;

class TopsPeriodServiceTest extends TestCase
{
    public function test_resolves_archived_month_period(): void
    {
        $period = (new TopsPeriodService)->resolve('2026-05');

        $this->assertSame('2026-05', $period['key']);
        $this->assertSame('May 2026', $period['label']);
        $this->assertTrue($period['is_archived_month']);
    }
}
