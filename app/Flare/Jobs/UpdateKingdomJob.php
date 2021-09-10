<?php

namespace App\Flare\Jobs;

use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Kingdom;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\Skill;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use App\Game\Messages\Events\GlobalMessageEvent;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class UpdateKingdomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Kingdom $user
     */
    public $kingdom;

    /**
     * Create a new job instance.
     *
     * @param Kingdom $kingdom
     */
    public function __construct(Kingdom $kingdom) {
        $this->kingdom = $kingdom;
    }

    public function handle(KingdomResourcesService $kingdomResourcesService) {
        $kingdomResourcesService->setKingdom($this->kingdom)->updateKingdom();
    }
}
