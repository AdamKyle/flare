<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\RandomNumber\LotteryRandomNumberGenerator;
use App\Flare\Values\MapNameValue;
use Illuminate\Console\Command;

class TestQuery extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-query';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle()
    {

        $this->line('Simulating 1/1 Million Chance with 45% Looting applied');
        $this->line('======================================================');
        $this->line('');

        $attempts = 1000000;
        $success = false;

        $baseChance = 1 / $attempts;
        $chanceOfSuccess = $baseChance * (1 + 0.0);

        for ($i = 1; $i <= $attempts; $i++) {
            if ($this->attemptToGainReward($chanceOfSuccess)) {
                $this->line('Attempt: ' . $i);
                $success = true;
                break; // Exit the loop on success
            }
        }

        $this->line('');

        if ($success) {
            $this->info('Player gained the reward!');
        } else {
            $this->info("Player did not gain the reward after $attempts attempts.");
        }
    }

    private function attemptToGainReward(float $chanceOfSuccess): bool
    {

        if ($this->getTrueRandomNumber(0, 1) <= $chanceOfSuccess) {
            return true; // Player gained the reward
        }

        return false; // Player did not gain the reward
    }

    private function getTrueRandomNumber($min, $max): float
    {
        $randomNumber = random_int($min * 1000, $max * 1000);

        return $randomNumber / 1000;
    }
}
