<?php

namespace App\Console\Commands;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\UnitInQueue;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteUnitQueueForUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:kingdom-queue {characterName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will clear buildings and units from the queue who\'s completed dat is before today.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $character = Character::where('name', $this->argument('characterName'))->first();

        if (is_null($character)) {
            $this->error('No character found for name supplied.');

            return;
        }

        $today = Carbon::today();

        UnitInQueue::where('character_id', $character->id)->where('completed_at', '<', $today)->delete();
        BuildingInQueue::where('character_id', $character->id)->where('completed_at', '<', $today)->delete();
    }
}
