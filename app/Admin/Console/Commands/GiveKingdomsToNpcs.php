<?php

namespace App\Admin\Console\Commands;

use App\Flare\Models\Character;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Flare\Models\User;

class GiveKingdomsToNpcs extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'npc:take-kingdoms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes kingdoms from banned users.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $users = User::where('is_banned', true)
                     ->where('unbanned_at', null)
                     ->whereDate('updated_at', '<', Carbon::now()->subDays(7)->toDateTimeString())
                     ->get();

        foreach ($users as $user) {
            $kingdoms = $user->character->kingdoms;

            if ($kingdoms->isNotEmpty()) {
                $this->giveNPCKingdoms($kingdoms, $user->character);
            }
        }
    }

    protected function giveNPCKingdoms(Collection $kingdoms, Character $character) {
        foreach ($kingdoms as $kingdom) {
            $kingdom->update([
                'character_id'   => null,
                'npc_owned'      => true,
                'current_morale' => 0.10
            ]);

            broadcast(new UpdateNPCKingdoms($kingdom->gameMap));
            broadcast(new UpdateGlobalMap($character));
        }
    }
}
