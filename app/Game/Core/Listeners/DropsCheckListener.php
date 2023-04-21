<?php

namespace App\Game\Core\Listeners;

use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Core\Events\DropsCheckEvent;


class DropsCheckListener {

    use CanHaveQuestItem;

    /**
     * @var DropCheckService
     */
    private DropCheckService $dropCheckService;

    /**
     * @param DropCheckService $dropCheckService
     */
    public function __construct(DropCheckService $dropCheckService) {
        $this->dropCheckService = $dropCheckService;
    }

    /**
     * Handle the event.
     *
     * @param DropsCheckEvent $event
     * @return void
     * @throws \Exception
     */
    public function handle(DropsCheckEvent $event) {
        $this->dropCheckService->process($event->character, $event->monster);
    }

}
