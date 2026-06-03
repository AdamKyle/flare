<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Values\AutomationType;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Jobs\AutomatedFactionLoyalty;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Traits\ResponseBuilder;

class FactionLoyaltyAutomationService
{

    use ResponseBuilder;

    /**
     * Time delay for the job
     */
    const int TIME_DELAY = 1;

    /**
     * @param CharacterCacheData $characterCacheData
     */
    public function __construct( private readonly CharacterCacheData $characterCacheData) {
    }

    /**
     * Begin the automation.
     *
     * @param Character $character
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @param string $attackType
     * @return void
     */
    public function beginAutomation(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc, string $attackType): void
    {
        $automation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => $attackType,
        ]);

        $factionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $automation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'started_at' => now(),
        ]);

        $character = $this->setCharacterCanCraft($character, false);

        event(new UpdateCharacterStatus($character));

        event(new AutomationLogUpdate($character->user->id, 'You have agreed to help the npc: ' . $factionLoyaltyNpc->npc->real_name . ', they are very happy with your choice and look forward to you starting in '.self::TIME_DELAY.' minute(s).'));

        event(new AutomationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, self::TIME_DELAY)->delay(now()->addMinutes(self::TIME_DELAY))->onConnection('long_running')->onQueue('default_long');;
    }

    /**
     * Stop the automation.
     *
     * @param Character $character
     * @return array
     */
    public function stopAutomation(Character $character): array {
        $characterAutomation = CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::FACTION_LOYALTY)->first();

        if (is_null($characterAutomation)) {
            return $this->errorResult('Nope. You don\'t own that.');
        }

        $characterAutomation->delete();

        FactionLoyaltyAutomation::where('character_id', $character->id)->whereNull('completed_at')->first()->update([
            'completed_at' => now(),
        ]);

        $this->characterCacheData->deleteCharacterSheet($character);

        $character = $this->setCharacterCanCraft($character, true);

        event(new AutomationTimeOut($character->user, 0));
        event(new AutomationStatus($character->user, false));
        event(new UpdateCharacterStatus($character));
        event(new AutomationLogUpdate($character->user->id, 'You have stopped helping the npc in question and now they are sad.'));

        return $this->successResult();
    }

    /**
     * Set whether the character can craft.
     *
     * @param Character $character
     * @param bool $canCraft
     * @return Character
     */
    private function setCharacterCanCraft(Character $character, bool $canCraft): Character
    {
        $character->update([
            'can_craft' => $canCraft,
            'can_craft_again_at' => null,
        ]);

        return $character->refresh();
    }
}