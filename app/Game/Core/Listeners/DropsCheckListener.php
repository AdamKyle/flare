<?php

namespace App\Game\Core\Listeners;

use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Traits\CanHaveQuestItem;

class DropsCheckListener
{
    use CanHaveQuestItem;

    private DropCheckService $dropCheckService;

    public function __construct(DropCheckService $dropCheckService)
    {
        $this->dropCheckService = $dropCheckService;
    }

    /**
     * Handle the event.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function handle(DropsCheckEvent $event)
    {
        $this->dropCheckService->process($event->character, $event->monster);
    }
}
