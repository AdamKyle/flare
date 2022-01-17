<?php

namespace App\Game\Core\Listeners;

use App\Flare\Models\Location;
use App\Flare\Values\LocationEffectValue;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\DropsCheckEvent;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Item;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Skills\Services\DisenchantService;
use Facades\App\Flare\Calculators\DropCheckCalculator;


class DropsCheckListener
{

    use CanHaveQuestItem;

    private $randomItemDropBuilder;

    private $disenchantService;

    public function __construct(DropCheckService $dropCheckService) {
        $this->dropCheckService = $dropCheckService;
    }

    /**
     * Handle the event.
     *
     * @param DropsCheckEvent $event
     * @return void
     */
    public function handle(DropsCheckEvent $event)
    {
        $this->dropCheckService->process($event->character, $event->monster, $event->adventure);
    }

}
