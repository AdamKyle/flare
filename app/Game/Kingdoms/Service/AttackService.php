<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Mail\GenericMail;
use App\Game\Kingdoms\Builders\AttackBuilder;
use App\Game\Kingdoms\Events\UpdateEnemyKingdomsMorale;
use App\Game\Kingdoms\Events\UpdateUnitMovementLogs;
use App\Game\Kingdoms\Handlers\NotifyHandler;
use Exception;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Flare\Events\KingdomServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Models\User;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Handlers\KingdomHandler;
use App\Game\Kingdoms\Handlers\UnitHandler;
use App\Game\Kingdoms\Handlers\SiegeHandler;
use App\Game\Kingdoms\Jobs\MoveUnits;
use App\Game\Messages\Events\GlobalMessageEvent;

class AttackService {

    /**
     * @var SiegeHandler $seigeHandler
     */
    private $siegeHandler;

    /**
     * @var UnitHandler $unitHandler
     */
    private $unitHandler;

    /**
     * @var KingdomHandler $kingdomHandler
     */
    private $kingdomHandler;

    /**
     * @var NotifyHandler $notifyHandler
     */
    private $notifyHandler;

    /**
     * @var AttackBuilder $attackBuilder
     */
    private $attackBuilder;

    /**
     * @var array $unitsSent
     */
    private $unitsSent = [];

    /**
     * @var array $survivingUnits
     */
    private $survivingUnits = [];

    /**
     * @var array $siegeUnits
     */
    private $siegeUnits = [];

    /**
     * @var array $regularUnits
     */
    private $regularUnits = [];

    /**
     * @var array $newRegularUnits
     */
    private $newRegularUnits = [];

    /**
     * @var array $newSiegeUnits
     */
    private $newSiegeUnits = [];

    /**
     * @var GameUnit|null $settler
     */
    private $settler = null;

    /**
     * AttackService constructor.
     *
     * @param SiegeHandler $siegeHandler
     * @param UnitHandler $unitHandler
     * @param KingdomHandler $kingdomHandler
     * @param NotifyHandler $notifyHandler
     * @param AttackBuilder $attackBuilder
     */
    public function __construct(
        SiegeHandler $siegeHandler,
        UnitHandler $unitHandler,
        KingdomHandler $kingdomHandler,
        NotifyHandler $notifyHandler,
        AttackBuilder $attackBuilder
    ) {
        $this->siegeHandler   = $siegeHandler;
        $this->unitHandler    = $unitHandler;
        $this->kingdomHandler = $kingdomHandler;
        $this->notifyHandler  = $notifyHandler;
        $this->attackBuilder  = $attackBuilder;
    }

    /**
     * Handles the actual attack.
     *
     * @param UnitMovementQueue $unitMovement
     * @param Character $character
     * @param int $defenderId
     * @throws Exception
     */
    public function attack(UnitMovementQueue $unitMovement, Character $character, int $defenderId) {
        $attackingUnits = $unitMovement->units_moving;
        $defender       = $this->attackBuilder->setDefender($unitMovement, $defenderId, $character)->getDefender();

        $this->notifyHandler    = $this->notifyHandler->setAttackingKingdom($unitMovement->from_kingdom)
                                                      ->setDefendingKingdom($unitMovement->to_kingdom);

        if (is_null($defender)) {
            return $this->cannotAttackDefender($unitMovement, $character, $defenderId);
        }

        $this->notifyHandler = $this->notifyHandler->setDefendingCharacter($this->attackBuilder->getDefendingCharacter())
                                                   ->setOldDefendingKingdom($defender->load('units', 'buildings')->toArray());

        $this->handleAttack($attackingUnits, $defender);

        $this->notifyHandler = $this->notifyHandler->setSentUnits($this->unitsSent)->setSurvivingUnits($this->survivingUnits);

        $defender = $this->kingdomHandler->setKingdom($defender->refresh())->decreaseMorale()->getKingdom();

        $settler = $this->findSettlerUnit();

        if (!is_null($settler)) {
            $this->settler = GameUnit::find($settler['unit_id']);

            return $this->handleSettlerUnit($defender, $unitMovement, $character);
        }

        $this->notifyHandler->setSentUnits($this->unitsSent)->notifyDefender(KingdomLogStatusValue::KINGDOM_ATTACKED, $defender);

        if (!$this->anySurvivingUnits()) {

            $this->notifyHandler->notifyAttacker(KingdomLogStatusValue::LOST, $defender, $character);

            $unitMovement->delete();
        } else {
            $this->notifyHandler->notifyAttacker(KingdomLogStatusValue::ATTACKED, $defender, $character);

            $this->returnUnits($defender, $unitMovement, $character);
        }
    }

    /**
     * Handle the actual attack.
     *
     * @param array $attackingUnits
     * @param Kingdom $defender
     */
    protected function handleAttack(array $attackingUnits, Kingdom $defender) {
        $this->siegeUnits       = $this->siegeHandler->fetchSiegeUnits($attackingUnits);
        $this->regularUnits     = $this->unitHandler->getRegularUnits($attackingUnits);

        if (!empty($this->siegeUnits)) {
            $this->newSiegeUnits  = $this->siegeHandler->attack($defender, $this->siegeUnits);
        }

        if (!empty($this->regularUnits)) {
            $this->newRegularUnits = $this->unitHandler->attack($defender, $this->regularUnits);
        }

        $this->unitsSent     = array_merge($this->regularUnits, $this->siegeUnits);
        $this->survivingUnits = array_merge($this->newRegularUnits, $this->newSiegeUnits);
    }

    /**
     * Return units when you cannot attack your own kingdom.
     *
     * @param UnitMovementQueue $unitMovement
     * @param int $defenderId
     */
    protected function cannotAttackDefender(UnitMovementQueue $unitMovement, Character $character, int $defenderId) {
        $defender       = $this->attackBuilder->setDefender($unitMovement, $defenderId)->getDefender();

        $this->unitsSent      = [];
        $this->survivingUnits = array_merge($this->regularUnits, $this->siegeUnits);

        return $this->returnUnits($defender, $unitMovement, $character);
    }

    /**
     * Are there any surviving units left?
     *
     * @return bool
     */
    protected function anySurvivingUnits(): bool {
        foreach ($this->survivingUnits as $unitInfo) {
            if ($unitInfo['amount'] > 0) {
                return true;
            }
        }

        return false;
    }


    /**
     * Handles settler actions.
     *
     * Either reduces the morale of the kingdom, takes the kingdom or if the last unit, results in a lost attack.
     *
     * @param Kingdom $defender
     * @param UnitMovementQueue $unitMovement
     * @param Character $character
     */
    protected function handleSettlerUnit(Kingdom $defender, UnitMovementQueue $unitMovement, Character $character) {
        if ($this->isSettlerTheOnlyUnitLeft()) {

            $this->notifyHandler = $this->notifyHandler->setNewDefendingKingdom($defender);

            $this->notifyHandler->notifyDefender(KingdomLogStatusValue::KINGDOM_ATTACKED, $defender);

            return $this->notifyHandler->notifyAttacker(KingdomLogStatusValue::LOST, $defender, $character);
        }

        return $this->attemptToSettleKingdom($defender, $unitMovement, $character);
    }

    /**
     * Returns surviving units to the kingdom they came from.
     *
     * If there are no surviving units, then you lost the battle.
     *
     * @param Kingdom $defender
     * @param UnitMovementQueue $unitMovement
     * @param Character $character
     */
    protected function returnUnits(Kingdom $defender, UnitMovementQueue $unitMovement, Character $character) {
        $timeToReturn = $this->getTotalReturnTime($this->survivingUnits, $unitMovement);

        if ($timeToReturn > 0) {
            $timeToReturnTimeStamp = now()->addMinutes($timeToReturn);

            $this->notifyHandler->notifyAttacker(KingdomLogStatusValue::UNITS_RETURNING, $defender, $character);

            $unitMovement->update([
                'units_moving' => [
                    'new_units' => $this->survivingUnits,
                    'old_units' => $this->unitsSent
                ],
                'completed_at' => $timeToReturnTimeStamp,
                'started_at' => now(),
                'moving_to_x' => $unitMovement->from_x,
                'moving_to_y' => $unitMovement->from_y,
                'from_x' => $unitMovement->moving_to_x,
                'from_y' => $unitMovement->moving_to_y,
                'is_moving' => true,
                'is_attacking' => false,
                'is_returning' => true,
            ]);

            $unitMovement = $unitMovement->refresh();

            UpdateUnitMovementLogs::dispatch($character);

            MoveUnits::dispatch($unitMovement->id, $defender->id, 'return', $character)->delay(now()->addMinutes($timeToReturn));
        }
    }

    /**
     * Attempts to settle the kingdom.
     *
     * @param Kingdom $defender
     * @param UnitMovementQueue $unitMovement
     * @param Character $character
     */
    protected function attemptToSettleKingdom(Kingdom $defender, UnitMovementQueue $unitMovement, Character $character) {
        $defender = $this->kingdomHandler->updateDefendersMorale($defender, $this->settler);

        $this->notifyHandler = $this->notifyHandler->setNewDefendingKingdom($defender);

        if ($defender->current_morale === 0 || $defender->current_morale === 0.0) {

            $this->kingdomHandler->takeKingdom($defender, $character, $this->survivingUnits);

            $this->notifyHandler = $this->notifyHandler->setOldDefendingKingdom($this->kingdomHandler->getOldKingdom());

            $this->notifyHandler->notifyDefender(KingdomLogStatusValue::LOST_KINGDOM, $defender);

            $this->notifyHandler->notifyAttacker(KingdomLogStatusValue::TAKEN, $defender, $character);

            $this->notifyHandler->kingdomHasFallenMessage($character);
        } else {

            $defender->current_morale -= .10;

            $defender->save();

            $defender = $defender->refresh();

            broadcast(new UpdateEnemyKingdomsMorale($defender));

            $this->notifyHandler->notifyDefender(KingdomLogStatusValue::KINGDOM_ATTACKED, $defender);

            $this->notifyHandler->notifyAttacker(KingdomLogStatusValue::ATTACKED, $defender, $character);

            $this->returnUnits($defender, $unitMovement, $character);
        }
    }

    /**
     * Is the settler unit the only one left?
     *
     * @return bool
     */
    protected function isSettlerTheOnlyUnitLeft(): bool {
        $allDead = false;

        foreach ($this->survivingUnits as $unitInfo) {
            if (!$unitInfo['settler']) {
                if ($unitInfo['amount'] === 0.0 || $unitInfo['amount'] === 0) {
                    $allDead = true;
                } else {
                    $allDead = false;
                }
            }
        }

        return $allDead;
    }

    /**
     * Get the total returning units.
     *
     * @param array $units
     * @return int|mixed
     */
    protected function getTotalReturnTime(array $units) {

        return $this->getTime($units);
    }

    /**
     * Finds the settler units among all your units.
     *
     * Returns the array of info or null.
     *
     * @return mixed|null
     */
    protected function findSettlerUnit() {

        if (empty($this->newRegularUnits)) {
            return null;
        }

        // If there is only one unit and it's a setler
        // Then it dies.
        if (count($this->newRegularUnits) === 1) {
            return null;
        }

        $settler = null;

        foreach ($this->newRegularUnits as $unitInfo) {
            if ($unitInfo['settler']) {
                $settler = $unitInfo;
            }
        }

        return $settler;
    }

    /**
     * Get the return time based on surving units.
     *
     * @param array $units
     * @return int|mixed
     */
    private function getTime(array $units) {
        $time = 0;

        foreach ($units as $unitInfo) {
            if ($unitInfo['amount'] > 0) {
                $time += $unitInfo['time_to_return'];
            }
        }

        return $time;
    }
}
