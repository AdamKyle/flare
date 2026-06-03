<?php

namespace Tests\Console;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class StripFactionLoyaltyFightDataCommandTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character)
            ->createAutomation();
    }

    public function tearDown(): void
    {
        $this->character = null;
        $this->factionLoyaltyFactory = null;

        parent::tearDown();
    }

    public function testCommandStripsFightDataFromExistingFactionLoyaltyFightLogs(): void
    {
        $factionLoyaltyAutomationLog = $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog();

        $factionLoyaltyAutomationLog->update([
            'fight_logs' => [
                [
                    'log_entry_id' => 'bounty-log-entry',
                    'outcome' => 'bounty_completed',
                    'monster_id' => 10,
                    'fight_data' => [
                        'health' => [
                            'current_character_health' => 100,
                        ],
                    ],
                    'stalled_attempt' => 0,
                ],
                [
                    'log_entry_id' => 'training-log-entry',
                    'outcome' => 'training_batch_completed',
                    'monster_id' => 20,
                    'stalled_attempt' => 0,
                ],
            ],
            'crafting_logs' => [
                [
                    'result' => 'crafted_training_item',
                    'target_item_id' => 30,
                ],
            ],
        ]);

        $this->assertEquals(0, $this->artisan('faction-loyalty:strip-fight-data'));

        $factionLoyaltyAutomationLog = FactionLoyaltyAutomationLog::find($factionLoyaltyAutomationLog->id);

        $this->assertEquals([
            [
                'log_entry_id' => 'bounty-log-entry',
                'outcome' => 'bounty_completed',
                'monster_id' => 10,
                'stalled_attempt' => 0,
            ],
            [
                'log_entry_id' => 'training-log-entry',
                'outcome' => 'training_batch_completed',
                'monster_id' => 20,
                'stalled_attempt' => 0,
            ],
        ], $factionLoyaltyAutomationLog->fight_logs);
        $this->assertEquals([
            [
                'result' => 'crafted_training_item',
                'target_item_id' => 30,
            ],
        ], $factionLoyaltyAutomationLog->crafting_logs);
    }
}
