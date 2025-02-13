<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\Skill;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
use App\Game\Kingdoms\Handlers\TooMuchPopulationHandler;
use App\Game\Kingdoms\Handlers\Traits\DestroyKingdom;
use App\Game\Kingdoms\Traits\CalculateMorale;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Flare\Values\UserOnlineValue;

class KingdomUpdateService
{
    use CalculateMorale, DestroyKingdom;

    private ?Kingdom $kingdom;

    private ?Character $character;

    private GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler;

    private TooMuchPopulationHandler $tooMuchPopulationHandler;

    private UpdateKingdom $updateKingdom;

    public function __construct(GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler,
        TooMuchPopulationHandler $tooMuchPopulationHandler,
        UpdateKingdom $updateKingdom)
    {
        $this->giveKingdomsToNpcHandler = $giveKingdomsToNpcHandler;
        $this->tooMuchPopulationHandler = $tooMuchPopulationHandler;
        $this->updateKingdom = $updateKingdom;
    }

    /**
     * Sets the kingdom.
     *
     * - Grabs the character from the kingdom, assuming the kingdom is not npc owned.
     *
     * @return $this
     */
    public function setKingdom(Kingdom $kingdom): KingdomUpdateService
    {
        $this->kingdom = $kingdom;

        if (! $this->kingdom->npc_owned) {
            $this->character = $this->kingdom->character;
        }

        if ($this->kingdom->npc_owned) {
            $this->character = null;
        }

        return $this;
    }

    /**
     * Get the kingdom.
     *
     * - Can be null due to handing over to the NPC, Destroying it,
     *   or the NPC kingdom being destroyed.
     */
    public function getKingdom(): ?Kingdom
    {
        if (is_null($this->kingdom)) {
            return null;
        }

        return $this->kingdom;
    }

    /**
     * Update a kingdom.
     */
    public function updateKingdom(): void
    {

        if (is_null($this->character)) {
            $lastWalked = $this->getLastTimeWalked();

            if ($lastWalked >= 30) {
                $this->destroyNPCKingdom();
            }

            $this->kingdom = null;

            return;
        }

        $character = $this->kingdom->character;

        $additionalLogData = [
            'kingdom_data' => [
                'x' => $this->kingdom->x_position,
                'y' => $this->kingdom->y_position,
                'name' => $this->kingdom->name,
                'game_map_name' => $this->kingdom->gameMap->name,
            ],
        ];

        if ($this->shouldGiveKingdomToNpc()) {
            $this->giveKingdomsToNpcHandler->giveKingdomToNPC($this->kingdom);

            event(new ServerMessageEvent($this->character->user, 'Your kingdom has been given over to the NPC: The Old Man. Kingdom has not been walked in 90 days or more.'));

            $gameMapName = $this->kingdom->gameMap->name;
            $xPosition = $this->kingdom->x_position;
            $yPosition = $this->kingdom->y_position;

            $this->kingdom = null;

            $additionalLogData['kingdom_data']['reason'] = 'Your kingdom was handed over to The Old Man and made a NPC kingdom because you did not walk it with in 90 days.';

            $this->createKingdomLog($character, $additionalLogData, KingdomLogStatusValue::NOT_WALKED);

            event(new GlobalMessageEvent('A kingdom on: '.$gameMapName.' at (X/Y): '.$xPosition.'/'.$yPosition.' has been neglected. The Old Man has taken it (New NPC Kingdom up for grabs).'));

            return;
        }

        if ($this->isTheOldManAngry()) {

            $this->tooMuchPopulationHandler->setKingdom($this->kingdom)->handleAngryNPC();

            $kingdom = $this->tooMuchPopulationHandler->getKingdom();

            if (is_null($kingdom)) {

                $additionalLogData['kingdom_data']['reason'] = 'Your kingdom was over populated and you could not afford the 10,000 Gold per additional person over your max population.';

                $this->createKingdomLog($character, $additionalLogData, KingdomLogStatusValue::OVER_POPULATED);

                return;
            }

            $this->kingdom = $kingdom;
        }

        $this->healBuildingsByPercentage();

        $this->updateKingdomMorale();

        $this->updateKingdomTreasury();

        $this->updateKingdomResources();

        $this->updateKingdomPopulation();

        $this->alertUsersOfKingdomRemoval();

        $this->updateKingdomProtectedUntil();
    }

    private function createKingdomLog(Character $character, array $additionalData, int $status)
    {

        $log = [
            'to_kingdom_id' => null,
            'from_kingdom_id' => null,
            'status' => $status,
            'published' => true,
            'additional_details' => $additionalData,
            'character_id' => $character->id,
            'old_buildings' => [],
            'new_buildings' => [],
            'old_units' => [],
            'new_units' => [],
            'morale_loss' => 1.0,
        ];

        KingdomLog::create($log);

        $this->updateKingdom->updateKingdomLogs($character);
    }

    /**
     * Update the protection status.
     *
     * @return void
     */
    private function updateKingdomProtectedUntil()
    {
        if (is_null($this->kingdom->protected_until)) {
            return;
        }

        $protectionIsOver = now()->gte($this->kingdom->protected_until);

        if ($protectionIsOver) {
            $this->kingdom->update([
                'protected_until' => null,
            ]);

            $this->alertUserToLossOfProtection();
        }

        $this->kingdom = $this->kingdom->refresh();
    }

    private function destroyNPCKingdom(): void
    {
        $this->destroyKingdom($this->kingdom);

        $this->kingdom = null;
    }

    /**
     * Is the current population greater than the max population?
     */
    private function isTheOldManAngry(): bool
    {

        $currentPopulation = $this->kingdom->current_population;
        $maxPopulation = $this->kingdom->max_population;

        return $currentPopulation > $maxPopulation;
    }

    /**
     * Should we give the kingdom to the NPC?
     *
     * - If the kingdom is not NPC Owned and never been walked, then yes, hand it over.
     * - If the kingdom has been walked and is not NPC owned, has it been walked in the last 30 days?
     *   - If the date since last walked is equal to or greater than 30 days, than hand it over.
     */
    private function shouldGiveKingdomToNpc(): bool
    {
        if (! $this->kingdom->npc_owned && is_null($this->kingdom->last_walked)) {
            return true;
        }

        if (! $this->kingdom->npc_owned && ! is_null($this->kingdom->last_walked)) {
            $lastTimeWalked = $this->getLastTimeWalked();

            if ($lastTimeWalked >= 90) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the last time walked in days.
     */
    private function getLastTimeWalked(): int
    {
        return $this->kingdom->last_walked->diffInDays(now());
    }

    /**
     * Heal 5% of the damage buildings.
     *
     * - Only heals if the buildings are not in queue.
     */
    private function healBuildingsByPercentage(): void
    {
        $buildings = $this->kingdom->buildings;

        foreach ($buildings as $building) {
            $isInQueue = BuildingInQueue::where('building_id', $building->id)
                ->where('kingdom_id', $this->kingdom->id)
                ->first();

            $currentDur = $building->current_durability;
            $maxDur = $building->max_durability;

            if ($currentDur < $maxDur && is_null($isInQueue)) {
                $currentDur = $currentDur + ($currentDur * 0.05);

                $building->update([
                    'current_durability' => ($currentDur > $maxDur) ? $maxDur : $currentDur,
                ]);
            }
        }

        $this->kingdom = $this->kingdom->refresh();
    }

    /**
     * Update the kingdom morale.
     *
     * - If a kingdom has not been walked in 30 days or more, it looses 10% of its morale.
     * - If a kingdom has buildings whose durability is less than then the buildings max durability
     *   and the building can increase/decrease the morale it will lose or gain morale based on the durability.
     */
    private function updateKingdomMorale(): void
    {
        $lastWalked = $this->getLastTimeWalked();
        $character = $this->kingdom->character;

        $morale = $this->reduceOrIncreaseMoraleForBuildings();

        if ($lastWalked >= 30) {
            $morale -= 0.10;

            event(new ServerMessageEvent(
                $character->user,
                $this->kingdom->name.
                ' is losing morale, due to not being walked for more than 30 days, at Location (x/y): '.
                $this->kingdom->x_position.'/'.$this->kingdom->y_position.' on the: '.
                $this->kingdom->gameMap->name.' plane.'
            ));
        }

        $this->kingdom->update([
            'current_morale' => $morale > $this->kingdom->max_morale ? $this->kingdom->max_morale : ($morale < 0 ? 0 : $morale),
        ]);

        $this->kingdom->refresh();
    }

    /**
     * Update the kingdom's treasury.
     *
     * - If the treasury is maxed or the morale is 0.0, we skip this.
     * - We add the kingmanship (or skill that effects kingdoms) skill bonus to the amount to give.
     * - We also divide the keep level by 100 to give an additional bonus.
     */
    private function updateKingdomTreasury(): void
    {
        if (KingdomMaxValue::isTreasuryAtMax($this->kingdom)) {
            return;
        }

        $character = $this->kingdom->character;

        if ($this->kingdom->current_morale >= 0.50) {
            $skill = $this->getCharacterSkillThatEffectsKingdoms($character);
            $keep = $this->getTheKeepBuilding();
            $currentTreasury = $this->kingdom->treasury;

            $total = (int) ceil($currentTreasury + $currentTreasury * ($skill->skill_bonus + ($keep->level / 100)));

            if ($total === 0) {
                $total = 1;
            }

            $this->kingdom->update([
                'treasury' => min($total, KingdomMaxValue::MAX_TREASURY),
            ]);

            $this->kingdom = $this->kingdom->refresh();
        }
    }

    /**
     * Updates a kingdom resources.
     *
     * - Updates for each resource: Wood, Clay, Stone and Iron
     * - If the building does not give resources, move on.
     * - If the buildings durability is 0 move on.
     * - Finally increase the resource of the building.
     */
    private function updateKingdomResources(): void
    {
        $resources = ['wood', 'clay', 'stone', 'iron'];

        foreach ($resources as $resource) {
            $building = $this->kingdom->buildings->where('gives_resources', true)->where('increase_in_'.$resource)->first();

            if ($building->current_durability === 0) {
                continue;
            }

            if (!is_null($building)) {
                $this->increaseResource($building, $resource);
            }
        }

        $this->kingdom = $this->kingdom->refresh();
    }

    /**
     * Update the kingdom population.
     *
     * - Won't update if the Farm durability is below 0.
     * - Gives partial population based on the durability of the farm building.
     * - Gives full resources if the building durability is maxed.
     */
    private function updateKingdomPopulation(): void
    {
        $building = $this->kingdom->buildings->where('is_farm', true)->first();
        $increaseAmount = $building->population_increase;
        $currentPop = $this->kingdom->current_population;
        $maxPop = $this->kingdom->max_population;
        $currentDur = $building->current_durability;
        $maxDur = $building->max_durability;

        if ($building->current_durability === 0) {
            return;
        }

        $percentage = $currentDur / $maxDur;

        if ($percentage < 1) {
            $newAmount = $currentPop + ($increaseAmount * $percentage);
        }

        if ($percentage >= 1) {
            $newAmount = $currentPop + $increaseAmount;
        }

        $this->kingdom->update([
            'current_population' => $newAmount > $maxPop ? $maxPop : $newAmount,
        ]);

        $this->kingdom = $this->kingdom->refresh();
    }

    /**
     * Reduce or increase the morale based on the building durability.
     */
    private function reduceOrIncreaseMoraleForBuildings(): float
    {
        return $this->calculateNewMorale($this->kingdom, $this->kingdom->current_morale);
    }

    /**
     * Increase the resources of the building.
     *
     * - If the building durability is not maxed, we take a percentage
     *   based on the current dur / max dur and give a percentage of what the building would
     *   give for resources.
     */
    private function increaseResource(KingdomBuilding $building, string $resource): void
    {
        $currentDurability = $building->current_durability;
        $maxDurability = $building->max_durability;
        $percentage = $currentDurability / $maxDurability;

        $increaseAmount = $building->{'increase_in_'.$resource};
        $currentAmount = $this->kingdom->{'current_'.$resource};
        $maxAmount = $this->kingdom->{'max_'.$resource};

        if ($percentage < 1) {
            $currentAmount += ($increaseAmount + $increaseAmount * $percentage);
        }

        if ($percentage === 1) {
            $currentAmount += $increaseAmount;
        }

        $this->kingdom->{'current_'.$resource} = min($currentAmount, $maxAmount);
        $this->kingdom->save();

        $this->kingdom = $this->kingdom->refresh();
    }

    /**
     * Fetches a skill which effects kingdoms.
     */
    private function getCharacterSkillThatEffectsKingdoms(Character $character): Skill
    {
        return $character->skills->filter(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::EFFECTS_KINGDOM;
        })->first();
    }

    /**
     * Fetches the keep building.
     */
    private function getTheKeepBuilding(): KingdomBuilding
    {
        return $this->kingdom->buildings()
            ->where('game_building_id', GameBuilding::where('name', 'Keep')->first()->id)
            ->first();
    }

    /**
     * Alerts the player their kingdom was updated.
     *
     * - Will send server message if the player is only and has the setting enabled.
     * - Will send email if the user is not online and has the setting enabled.
     * - Updates the player kingdom.
     */
    private function alertUsersOfKingdomRemoval(): void
    {
        $user = $this->kingdom->character->user;

        if (UserOnlineValue::isOnline($user) && $user->show_kingdom_update_messages) {
            $x = $this->kingdom->x_position;
            $y = $this->kingdom->y_position;
            $gameMap = $this->kingdom->gameMap;

            event(new ServerMessageEvent(
                $user,
                $this->kingdom->name.' Was updated per the hourly update at (X/Y): '.$x.'/'.$y.
                ' On plane: '.$gameMap->name
            ));
        }

        $this->updateKingdom->updateKingdom($this->kingdom);
    }

    private function alertUserToLossOfProtection(): void
    {
        $user = $this->kingdom->character->user;

        if (UserOnlineValue::isOnline($user) && $user->show_kingdom_update_messages) {
            $x = $this->kingdom->x_position;
            $y = $this->kingdom->y_position;
            $gameMap = $this->kingdom->gameMap;

            event(new ServerMessageEvent(
                $user,
                $this->kingdom->name.' lost its protection and is open to everyone now at (X/Y): '.$x.'/'.$y.
                ' On plane: '.$gameMap->name
            ));
        }

        $this->updateKingdom->updateKingdom($this->kingdom);
    }
}
