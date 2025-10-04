<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use Illuminate\Console\Command;

class ManageKingdomResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manage:kingdom-resources
                            {characterId}
                            {customAmount?}
                            {--setMaxResources}
                            {--some}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage kingdom resources for a character.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('This will not set population amounts.');

        $characterId = $this->argument('characterId');
        $customAmount = intval($this->argument('customAmount'));
        $shouldSetMaxResources = $this->option('setMaxResources');
        $useSome = $this->option('some');

        $character = Character::find($characterId);

        if (is_null($character)) {
            $this->error('No character found for the provided ID.');

            return;
        }

        if ($character->kingdoms->isEmpty()) {
            $this->error('Character does not have any kingdoms to act on.');

            return;
        }

        if ($customAmount < 0) {
            $this->error('Custom amount must be 0 or higher.');

            return;
        }

        if ($useSome) {
            $fraction = $this->askForFraction();
            $this->updateSomeKingdoms($character, $customAmount, $fraction, $shouldSetMaxResources);

            return;
        }

        if ($shouldSetMaxResources) {
            $this->setMaxResources($character);

            return;
        }

        $this->updateAllKingdoms($character, $customAmount);
    }

    /**
     * Set all resources to their maximum values.
     */
    protected function setMaxResources($character)
    {
        $character->kingdoms()->chunk(100, function ($kingdoms) {
            foreach ($kingdoms as $kingdom) {
                $kingdom->update([
                    'current_wood' => $kingdom->max_wood,
                    'current_clay' => $kingdom->max_clay,
                    'current_stone' => $kingdom->max_stone,
                    'current_iron' => $kingdom->max_iron,
                ]);
            }
        });

        $this->info('Maxed out the kingdom resources.');
    }

    /**
     * Ask the user to choose a valid fraction (0.25, 0.5, 0.75).
     */
    protected function askForFraction()
    {
        $fraction = $this->choice(
            'Choose a fraction of kingdoms to update:',
            ['0.25', '0.5', '0.75']
        );

        return (float) $fraction;
    }

    /**
     * Update only some kingdoms based on the chosen fraction.
     */
    protected function updateSomeKingdoms($character, $customAmount, $fraction, $shouldSetMaxResources)
    {
        $kingdoms = $character->kingdoms;
        $totalKingdoms = $kingdoms->count();
        $kingdomsToUpdate = $kingdoms->random(round($totalKingdoms * $fraction));

        if (! $shouldSetMaxResources && $customAmount <= 0) {
            $this->error('When passing --some, you need to also pass customAmount so that the rest of the kingdoms use the custom amount, while the "some" uses 0');
            $this->info('Also note you may pass --setMaxResources in order to set most to mtheir max resources, while the rest you selected as some, will be set to 0.');

            return;
        }

        // Set the selected kingdoms to the custom amount
        foreach ($kingdomsToUpdate as $kingdom) {
            $kingdom->update([
                'current_wood' => $shouldSetMaxResources ? $kingdom->max_wood : $customAmount,
                'current_clay' => $shouldSetMaxResources ? $kingdom->max_clay : $customAmount,
                'current_stone' => $shouldSetMaxResources ? $kingdom->max_stone : $customAmount,
                'current_iron' => $shouldSetMaxResources ? $kingdom->max_iron : $customAmount,
            ]);
        }

        $kingdomNames = [];

        // Set the rest of the kingdoms to 0
        $remainingKingdoms = $kingdoms->diff($kingdomsToUpdate);
        foreach ($remainingKingdoms as $kingdom) {
            $kingdom->update([
                'current_wood' => 0,
                'current_clay' => 0,
                'current_stone' => 0,
                'current_iron' => 0,
            ]);

            $kingdomNames[] = $kingdom->name;
        }

        $this->info("Updated {$kingdomsToUpdate->count()} kingdoms to: ".($shouldSetMaxResources ? 'Max Resources' : $customAmount));
        $this->info("Set the remaining {$remainingKingdoms->count()} kingdoms to 0.");
        $this->info('Kingdoms who have 0 resources (excluding population): '.implode(', ', $kingdomNames));
    }

    /**
     * Update all kingdoms to the custom amount.
     */
    protected function updateAllKingdoms($character, $customAmount)
    {
        $character->kingdoms()->update([
            'current_wood' => $customAmount,
            'current_clay' => $customAmount,
            'current_stone' => $customAmount,
            'current_iron' => $customAmount,
        ]);

        $this->info("Set all kingdoms to the custom amount: $customAmount.");
    }
}
