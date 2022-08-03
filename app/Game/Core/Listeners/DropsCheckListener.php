<?php

namespace App\Game\Core\Listeners;

use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Core\Events\DropsCheckEvent;


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
