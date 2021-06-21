<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use Cache;
use Illuminate\Support\Collection;
use Mail;
use Illuminate\Console\Command;
use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Kingdoms\Mail\KingdomsUpdated;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use Facades\App\Flare\Values\UserOnlineValue;

class UpdateKingdom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:kingdom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the kingdom\'s per hour resources.';

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
     * @return int
     */
    public function handle(KingdomResourcesService $service)
    {
        Kingdom::chunkById(100, function($kingdoms) use ($service) {
            foreach ($kingdoms as $kingdom) {
                $service->setKingdom($kingdom)->updateKingdom();
            }
        });

        User::chunkById(100, function($users) {
            foreach ($users as $user) {
                if (Cache::has('kingdoms-updated-' . $user->id)) {
                    if ($user->kingdoms_update_email && !UserOnlineValue::isOnline($user)) {
                        $kingdoms = $this->getKingdomEmailData(Cache::pull('kingdoms-updated-' . $user->id));

                        Mail::to($user->email)->send((new KingdomsUpdated($user, $kingdoms)));
                    } else {
                        Cache::delete('kingdoms-updated-' . $user->id);
                    }
                }
            }
        });
    }

    protected function getKingdomEmailData(array $kingdoms) {
        $kingdomData = [];

        foreach ($kingdoms as $kingdomId) {
            $kingdom = Kingdom::find($kingdomId);

            $kingdomData[] = [
                'name'       => $kingdom->name,
                'x_position' => $kingdom->x_position,
                'y_position' => $kingdom->y_position,
                'plane'      => $kingdom->gameMap->name,
            ];
        }

        return $kingdomData;
    }
}
