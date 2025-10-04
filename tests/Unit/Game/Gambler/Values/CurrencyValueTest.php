<?php

namespace Tests\Unit\Game\Gambler\Values;

use App\Game\Gambler\Values\CurrencyValue;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyValueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_initialize_currency_value_with_in_proper_value()
    {
        $this->expectException(Exception::class);

        new CurrencyValue(13);
    }
}
