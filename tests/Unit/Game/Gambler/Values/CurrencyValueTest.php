<?php

namespace Tests\Unit\Game\Gambler\Values;

use App\Game\Gambler\Values\CurrencyValue;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyValueTest extends TestCase {

    use RefreshDatabase;


    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testInitializeCurrencyValueWithInProperValue() {
        $this->expectException(Exception::class);

        new CurrencyValue(13);
    }
}
