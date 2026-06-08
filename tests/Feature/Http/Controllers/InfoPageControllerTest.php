<?php

namespace Tests\Feature\Http\Controllers;

use App\Flare\Models\InfoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfoPageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testPublicInformationSearchReturnsMatchingPages(): void
    {
        InfoPage::create([
            'page_name' => 'combat-guide',
            'page_sections' => [[
                'order' => 1,
                'content' => 'Critical strikes deal additional damage.',
            ]],
        ]);

        $response = $this->call('GET', route('info.search', [
            'info_search' => 'Critical strikes',
        ]));

        $response->assertOk();
        $response->assertSee('Combat guide');
    }

    public function testMaliciousInformationSearchCannotAlterOrBreakQuery(): void
    {
        InfoPage::create([
            'page_name' => 'secret-page',
            'page_sections' => [[
                'order' => 1,
                'content' => 'This page should not match malicious input.',
            ]],
        ]);

        $response = $this->call('GET', route('info.search', [
            'info_search' => "%' OR 1=1 -- ",
        ]));

        $response->assertOk();
        $response->assertDontSee('Secret Page');
    }

    public function testSearchHighlightingStillWorksForNormalQuery(): void
    {
        InfoPage::create([
            'page_name' => 'combat-guide',
            'page_sections' => [[
                'order' => 1,
                'content' => 'Critical strikes deal additional damage.',
            ]],
        ]);

        $response = $this->call('GET', route('info.search', [
            'info_search' => 'strikes',
        ]));

        $response->assertOk();
        $response->assertSee('<strong>strikes</strong>', false);
    }

    public function testDotStarQueryDoesNotHighlightEntireSnippet(): void
    {
        InfoPage::create([
            'page_name' => 'combat-guide',
            'page_sections' => [[
                'order' => 1,
                'content' => 'Use .* to match any character in patterns.',
            ]],
        ]);

        $response = $this->call('GET', route('info.search', [
            'info_search' => '.*',
        ]));

        $response->assertOk();
        $response->assertDontSee('<strong>Use', false);
    }

    public function testUnclosedParenthesisQueryDoesNotDropSnippet(): void
    {
        InfoPage::create([
            'page_name' => 'combat-guide',
            'page_sections' => [[
                'order' => 1,
                'content' => 'Buying (special item) costs 1000 gold.',
            ]],
        ]);

        $response = $this->call('GET', route('info.search', [
            'info_search' => '(',
        ]));

        $response->assertOk();
        $response->assertSee('Buying');
    }
}
