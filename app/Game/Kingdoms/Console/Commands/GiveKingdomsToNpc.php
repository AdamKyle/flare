<?php

namespace App\Game\Kingdoms\Console\Commands;

use App\Flare\Models\User;
use Illuminate\Console\Command;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use App\Game\Kingdoms\Jobs\GiveKingdomsToNPC as GiveKingdomsToNPCJob;

class GiveKingdomsToNpc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'npc:give-kingdoms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give npcs kingdoms from banned players.';

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
    public function handle()
    {
        $npc = Npc::where('type', NpcTypes::KINGDOM_HOLDER)->first();

        if (is_null($npc)) {
            return;
        }

        $users = User::where('is_banned', true)->whereNull('unbanned_at')->get();

        foreach ($users as $user) {
            if (!is_null($user->un_ban_request)) {
                if ($user->updated_at->greaterThan(now()->subDays(3))) {
                    GiveKingdomsToNPCJob::dispatch($user)->delay(now()->addMinutes(1));
                }
            } else {
                GiveKingdomsToNPCJob::dispatch($user)->delay(now()->addMinutes(1));
            }
        }
    }
}
