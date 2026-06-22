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
use Illuminate\Support\Facades\Log;

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
        Log::channel('faction_loyalty')->info('Faction loyalty automation requested.', [
            'character_id' => $character->id,
            'character_name' => $character->name,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'npc_name' => $factionLoyaltyNpc->npc?->real_name,
            'attack_type' => $attackType,
        ]);

        Log::info('Faction loyalty automation starting.', [
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'attack_type' => $attackType,
        ]);

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

        Log::channel('faction_loyalty')->info('Faction loyalty automation records created.', [
            'character_id' => $character->id,
            'character_name' => $character->name,
            'automation_id' => $automation->id,
            'faction_loyalty_automation_id' => $factionLoyaltyAutomation->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'completed_at' => $automation->completed_at,
        ]);

        $character = $this->setCharacterCanCraft($character, false);

        event(new UpdateCharacterStatus($character));

        event(new AutomationLogUpdate($character->user->id, 'You have agreed to help the npc: ' . $factionLoyaltyNpc->npc->real_name . ', they are very happy with your choice and look forward to you starting in '.self::TIME_DELAY.' minute(s).'));

        event(new AutomationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        Log::channel('faction_loyalty')->info('Faction loyalty automation dispatch requested.', [
            'character_id' => $character->id,
            'character_name' => $character->name,
            'automation_id' => $automation->id,
            'faction_loyalty_automation_id' => $factionLoyaltyAutomation->id,
            'connection' => 'long_running',
            'queue' => 'faction_loyalty',
            'delay_minutes' => self::TIME_DELAY,
        ]);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, self::TIME_DELAY)->delay(now()->addMinutes(self::TIME_DELAY))->onConnection('long_running')->onQueue('faction_loyalty');

        Log::channel('faction_loyalty')->info('Faction loyalty automation dispatch completed.', [
            'character_id' => $character->id,
            'character_name' => $character->name,
            'automation_id' => $automation->id,
            'faction_loyalty_automation_id' => $factionLoyaltyAutomation->id,
            'connection' => 'long_running',
            'queue' => 'faction_loyalty',
            'delay_minutes' => self::TIME_DELAY,
        ]);
    }

    /**
     * Stop the automation.
     *
     * @param Character $character
     * @return array
     */
    public function stopAutomation(Character $character): array {
        $characterAutomation = CharacterAutomation::where('character_id', $character->id)
            ->where('type', AutomationType::FACTION_LOYALTY)
            ->where('completed_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (is_null($characterAutomation)) {
            Log::channel('faction_loyalty')->warning('Faction loyalty automation stop requested but no active automation found.', [
                'character_id' => $character->id,
                'character_name' => $character->name,
            ]);

            Log::warning('Faction loyalty automation stop requested but no active automation found.', [
                'character_id' => $character->id,
            ]);

            return $this->errorResult('Nope. You don\'t own that.');
        }

        $factionLoyaltyAutomation = FactionLoyaltyAutomation::where('character_automation_id', $characterAutomation->id)
            ->first();

        Log::channel('faction_loyalty')->info('Faction loyalty automation stopped by player.', [
            'character_id' => $character->id,
            'character_name' => $character->name,
            'automation_id' => $characterAutomation->id,
            'faction_loyalty_automation_id' => $factionLoyaltyAutomation?->id,
        ]);

        Log::info('Faction loyalty automation stopping by player request.', [
            'character_id' => $character->id,
            'automation_id' => $characterAutomation->id,
        ]);

        $characterAutomation->delete();

        $factionLoyaltyAutomation?->update(['completed_at' => now()]);

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
