<?php

namespace App\Game\Character\CharacterInventory\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\SkillCheckService;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DisenchantMany implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array  $slotIds
     */
    public function __construct(protected readonly Character $character, protected readonly array $itemIds) {}

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle(DisenchantService $disenchantService, SkillCheckService $skillCheckService)
    {
        $character = $this->character;

        $disenchantingSkill = $character->skills->filter(function ($skill) {
            return $skill->type()->isDisenchanting();
        })->first();

        $characterRoll = $skillCheckService->characterRoll($disenchantingSkill);
        $dcCheck = $skillCheckService->getDCCheck($disenchantingSkill);

        $disenchanted = $characterRoll >= $dcCheck;

        foreach ($this->itemIds as $itemId) {
            $item = Item::find($itemId);

            if ($character->gold_dust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
                $this->processCappedGoldDust($character, $item, $disenchanted);

                $character = $character->refresh();

                continue;
            }

            $this->processDisenchant($disenchantService, $character, $disenchantingSkill, $item, $disenchanted);

            $character = $character->refresh();
        }

        ServerMessageHandler::sendBasicMessage($character->user, 'Look at that! disenchanted all your valid items!');
    }

    private function processCappedGoldDust(Character $character, Item $item, bool $disenchanted): void
    {
        $message = 'You are maxed on gold dust and '.(
            $disenchanted ? ' you still managed to disenchant the item: '.$item->affix_name :
            'you failed to disenchant the item: '.$item->affix_name
        );

        ServerMessageHandler::sendBasicMessage($character->user, $message);
    }

    private function processDisenchant(DisenchantService $disenchantService, Character $character, Skill $disenchantingSkill, Item $item, bool $disenchanted): void
    {
        event(new UpdateSkillEvent($disenchantingSkill));

        $message = 'You '.(
            $disenchanted ? 'disenchanted the item: '.$item->affix_name :
            'failed to disenchant the item: '.$item->affix_name
        );

        ServerMessageHandler::sendBasicMessage($character->user, $message);

        $goldDust = $disenchantService->setUp($character)->updateGoldDust($character, ! $disenchanted);

        $message = 'You also gained: '.number_format($goldDust).' Gold Dust!';

        ServerMessageHandler::sendBasicMessage($character->user, $message);
    }
}
