<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use VCR\VCR;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\ReleaseNote;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMarketHistory;

class FetchTagDataTest extends TestCase
{
    use RefreshDatabase, CreateMarketHistory, CreateItem;

    /**
     * We use VCR here to capture the request.
     * 
     * This will allow us to run the test with out the actual call being
     * made to github.
     * 
     * The first time this was run, a VCR Cassette was made. This Cassette
     * is then saved and comitted with the app.
     * 
     * The Cassete is stored in tests/fixtures. The token was removed for secuity reasons.
     */
    public function testFetchTagData()
    {
        VCR::turnOn();

        VCR::insertCassette('github-release');

        $this->assertEquals(0, $this->artisan('fetch:tag-data'));

        $this->assertTrue(ReleaseNote::all()->isNotEmpty());

        VCR::eject();

        VCR::turnOn();
    }
}
