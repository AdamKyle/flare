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

    /**
     * Execute the console command.
     */
    public function handle(LotteryRandomNumberGenerator $lotteryRandomNumberGenerator) {

        // Example: Generate a true random number between 1 and 1,000,000
        $min = 1;
        $max = 1000000;

        for ($i =0; $i <= 10000; $i++) {
            $randomNumber = $this->generateTrueRandomNumber($min, $max);
            $number       = $lotteryRandomNumberGenerator->generateNumber($randomNumber);

            if ($number >= 96) {
                $this->line('HOLY SHIT!!!! :: ' . $number);
                return;
            } else {
                $this->line('['.$i.'] Number: ' . $number);
            }
        }
    }


    public function generateTrueRandomNumber($min, $max)
    {
        do {
            $randomBytes = random_bytes(4); // Adjust the number of bytes based on your needs
            $randomNumber = hexdec(bin2hex($randomBytes));
        } while ($randomNumber < $min || $randomNumber > $max);

        return $randomNumber;
    }
}
