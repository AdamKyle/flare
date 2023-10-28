<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Flare\Models\Character;
use App\Game\Battle\Events\UpdateCharacterStatus;

class ResetTimers extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:timers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle() {
        $this->resetCraftingTimers();
        $this->resetCharacterMovement();
        $this->resetCharacterAttack();
    }

    protected function resetCraftingTimers() {
        $characters = Character::whereNotNull('can_craft_again_at')
            ->where('can_craft_again_at', '<', now())
            ->where('can_craft', false)
            ->get();

        foreach ($characters as $character) {
            $character->update([
                'can_craft_again_at' => null,
                'can_craft'          => true,
            ]);

            event(new UpdateCharacterStatus($character->refresh()));
        }
    }

    protected function resetCharacterAttack() {
        $characters = Character::whereNotNull('can_attack_again_at')
            ->where('can_attack_again_at', '<', now())
            ->where('can_attack', false)
            ->get();

        foreach ($characters as $character) {
            $character->update([
                'can_attack_again_at' => null,
                'can_attack' => true,
            ]);

            event(new UpdateCharacterStatus($character->refresh()));
        }
    }

    protected function resetCharacterMovement() {
        $characters = Character::whereNotNull('can_move_again_at')
            ->where('can_move_again_at', '<', now())
            ->where('can_move', false)
            ->get();

        foreach ($characters as $character) {
            $character->update([
                'can_move_again_at' => null,
                'can_move' => true,
            ]);

            event(new UpdateCharacterStatus($character->refresh()));
        }
    }
}
