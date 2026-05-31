<?php

namespace App\Console\AfterDevelopment;

use App\Flare\Models\FactionLoyaltyAutomationLog;
use Illuminate\Console\Command;

class StripFactionLoyaltyFightData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faction-loyalty:strip-fight-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Strips full fight data from faction loyalty automation fight logs.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updatedLogs = 0;

        FactionLoyaltyAutomationLog::query()
            ->whereNotNull('fight_logs')
            ->chunkById(100, function ($factionLoyaltyAutomationLogs) use (&$updatedLogs) {
                foreach ($factionLoyaltyAutomationLogs as $factionLoyaltyAutomationLog) {
                    $fightLogs = $factionLoyaltyAutomationLog->fight_logs ?? [];

                    $strippedFightLogs = array_map(function (array $fightLog): array {
                        unset($fightLog['fight_data']);

                        return $fightLog;
                    }, $fightLogs);

                    if ($fightLogs === $strippedFightLogs) {
                        continue;
                    }

                    $factionLoyaltyAutomationLog->update([
                        'fight_logs' => $strippedFightLogs,
                    ]);

                    $updatedLogs++;
                }
            });

        $this->info('Updated ' . $updatedLogs . ' faction loyalty automation logs.');

        return 0;
    }
}
