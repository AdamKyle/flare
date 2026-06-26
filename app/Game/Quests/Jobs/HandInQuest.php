<?php

namespace App\Game\Quests\Jobs;

use App\Flare\Models\Quest;
use Illuminate\Bus\Queueable;
use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Handlers\NpcQuestsHandler;
use Exception;
use Illuminate\Support\Facades\Log;

class HandInQuest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Character $character;

    protected Quest $quest;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Character $character, Quest $quest)
    {
        $this->character = $character;
        $this->quest = $quest;
    }

    /**
     * Execute the job.
     */
    public function handle(NpcQuestsHandler $npcQuestsHandler): void
    {
        try {
            $npcQuestsHandler->handleNpcQuest($this->character, $this->quest);
            $npcQuestsHandler->questRewardHandler()->createquestQuestLog($this->character, $this->quest);
            event(new GlobalMessageEvent($this->character->name . ' Has completed a quest (' . $this->quest->name . ') for: ' . $this->quest->npc->real_name . ' and been rewarded with a godly gift!'));
            $npcQuestsHandler->questRewardHandler()->processXpReward($this->quest, $this->character->refresh());
        } catch (Exception $e) {
            Log::error($e->getMessage());

            throw $e;
        }
    }
}
