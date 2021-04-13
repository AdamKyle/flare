<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Mail\GenericMail;
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
     * @var array $oldDefender
     */
    private $oldDefender = [];

    /**
     * @var array $newDefender
     */
    private $newDefender = [];

    /**
     * AttackService constructor.
     *
     * @param SiegeHandler $siegeHandler
     * @param UnitHandler $unitHandler
     * @param KingdomHandler $kingdomHandler
     */
    public function __construct(SiegeHandler $siegeHandler, UnitHandler $unitHandler, KingdomHandler $kingdomHandler) {
        $this->siegeHandler   = $siegeHandler;
        $this->unitHandler    = $unitHandler;
        $this->kingdomHandler = $kingdomHandler;
    }

    /**
     * Handles the actual attack.
     *
     * @param UnitMovementQueue $unitMovement
     * @param int $defenderId
     */
    public function attack(UnitMovementQueue $unitMovement, Character $character, int $defenderId) {
        $attackingUnits = $unitMovement->units_moving;
        $defender       = Kingdom::where('id', $defenderId)
                                 ->where('x_position', $unitMovement->moving_to_x)
                                 ->where('y_position', $unitMovement->moving_to_y)
                                 ->first();

        $this->oldDefender = $defender->load('units')->toArray();

        $this->siegeUnits   = $this->fetchSiegeUnits($attackingUnits);
        $this->regularUnits = $this->getRegularUnits($attackingUnits);

        if (!empty($this->siegeUnits)) {
            $healers               = $this->fetchHealers($attackingUnits);
            $this->newSiegeUnits   = $this->siegeHandler->attack($defender, $this->siegeUnits, $healers);
        }

        if (!empty($this->regularUnits)) {
            $this->newRegularUnits = $this->unitHandler->attack($defender, $this->regularUnits);
        }

        $this->unitsSent      = array_merge($this->regularUnits, $this->siegeUnits);
        $this->survivingUnits = array_merge($this->newRegularUnits, $this->newSiegeUnits);

        $defender = $this->kingdomHandler->setKingdom($defender)->decreaseMorale()->getKingdom();

        $settler = $this->findSettlerUnit($this->unitsSent);

        if (!is_null($settler)) {
            $this->settler = GameUnit::find($settler['unit_id']);

            return $this->handleSettlerUnit($defender, $unitMovement, $character);
        }

        $this->newDefender = $defender->load('units')->toArray();

        $this->notifyAttacker(KingdomLogStatusValue::ATTACKED, $defender, $unitMovement, $character);

        $this->notifyDefender(KingdomLogStatusValue::KINGDOM_ATTACKED, $defender->character, $defender);

        $this->returnUnits($defender, $unitMovement, $character);
    }

    /**
     * Fetches the healer units.
     *
     * This is called when sending siege units into battle.
     *
     * @param array $attackingUnits
     * @return array
     */
    public function fetchHealers(array $attackingUnits): array {
        $healerUnits = [];

        foreach ($attackingUnits as $unitInfo) {
            $gameUnit = GameUnit::where('id', $unitInfo['unit_id'])->where('can_heal', true)->first();

            if (is_null($gameUnit)) {
                continue;
            }

            $healerUnits[] = [
                'amount'   => $unitInfo['amount'],
                'heal_for' => $gameUnit->heal_percentage * $unitInfo['amount'],
                'unit_id'  => $gameUnit->id,
            ];
        }

        return $healerUnits;
    }

    /**
     * Fetches the siege units.
     *
     * @param array $attackingUnits
     * @return array
     */
    public function fetchSiegeUnits(array $attackingUnits): array {
        $siegeUnits = [];

        forEach($attackingUnits as $unitInfo) {
            $gameUnit = GameUnit::where('id', $unitInfo['unit_id'])->where('siege_weapon', true)->first();

            if (!is_null($gameUnit)) {
                $siegeUnits[] = [
                    'amount'         => $unitInfo['amount'],
                    'total_attack'   => $gameUnit->attack * $unitInfo['amount'],
                    'total_defence'  => $gameUnit->defence * $unitInfo['amount'],
                    'primary_target' => $gameUnit->primary_target,
                    'fall_back'      => $gameUnit->fall_back,
                    'unit_id'        => $gameUnit->id,
                    'time_to_return' => $unitInfo['time_to_return'],
                    'settler'        => false,
                ];
            }
        }

        return $siegeUnits;
    }

    /**
     * Gets the regular non siege units.
     *
     * @param array $attackingUnits
     * @return array
     */
    public function getRegularUnits(array $attackingUnits): array {
        $regularUnits = [];

        forEach($attackingUnits as $unitInfo) {
            $gameUnit = GameUnit::where('id', $unitInfo['unit_id'])->where('siege_weapon', false)->first();

            if (!is_null($gameUnit)) {
                $regularUnits[] = [
                    'amount'           => $unitInfo['amount'],
                    'total_attack'     => $gameUnit->attack * $unitInfo['amount'],
                    'total_defence'    => $gameUnit->defence * $unitInfo['amount'],
                    'primary_target'   => $gameUnit->primary_target,
                    'fall_back'        => $gameUnit->fall_back,
                    'unit_id'          => $gameUnit->id,
                    'healer'           => $gameUnit->can_heal,
                    'heal_for'         => !is_null($gameUnit->heal_percentage) ? $gameUnit->heal_percentage * $unitInfo['amount'] : 0,
                    'can_be_healed'    => !$gameUnit->can_not_be_healed,
                    'settler'          => $gameUnit->is_settler,
                    'time_to_return'   => $unitInfo['time_to_return'],
                ];
            }
        }

        return $regularUnits;
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
        dump('Handle Settler Unit: ');
        dump($this->newRegularUnits, $this->survivingUnits);
        if ($this->isSettlerTheOnlyUnitLeft($this->survivingUnits)) {

            $this->newDefender = $defender->load('units')->toArray();

            $this->notifyDefender(KingdomLogStatusValue::KINGDOM_ATTACKED, $defender->character, $defender);

            return $this->notifyAttacker(KingdomLogStatusValue::LOST, $defender, $unitMovement, $character);
        }

        return $this->attemptToSettleKingdom($defender, $unitMovement, $character);
    }

    /**
     * Notify the defender of any messages relating to their kingdom.
     *
     * @param string $status
     * @param Character $character
     * @param Kingdom $defender
     * @throws \Exception
     */
    protected function notifyDefender(string $status, Character $character, Kingdom $defender) {
        dump('Called');
        KingdomLog::create([
            'character_id' => $character->id,
            'status'       => $status,
            'old_defender' => $this->oldDefender,
            'new_defender' => $this->newDefender,
            'published'    => true,
        ]);

        $status = new KingdomLogStatusValue($status);

        $message = '';
        $type    = '';

        if ($status->kingdomWasAttacked()) {

            $message = 'Your kingdom ' . $defender->name . ' at (X/Y) ' . $defender->x_position .
                '/' . $defender->y_position . ' on the ' .
                $defender->gameMap->name . ' plane, was attacked. Check your attack logs for more info.';

            $type = 'kingdom-attacked';
        }

        if ($status->lostKingdom()) {
            $message = 'Your kingdom ' . $defender->name . ' at (X/Y) ' . $defender->x_position .
                '/' . $defender->y_position . ' on the ' .
                $defender->gameMap->name . ' plane, was taken. Check your attack logs for more info.';

            $type = 'kingdom-taken';
        }

        $this->sendMessage($character->user, $type, $message);
    }

    /**
     * Notify the attacker of any messages related to the attack.
     *
     * @param string $status
     * @param Kingdom $defender
     * @param UnitMovementQueue $unitMovement
     * @param Character $character
     */
    protected function notifyAttacker(string $status, Kingdom $defender, UnitMovementQueue $unitMovement, Character $character) {

        $logStatus = new KingdomLogStatusValue($status);

        KingdomLog::create([
            'character_id'      => $character->id,
            'from_kingdom_id'   => $defender->id,
            'to_kingdom_id'     => $unitMovement->to_kingdom->id,
            'status'            => $status,
            'units_sent'        => $this->unitsSent,
            'units_survived'    => $this->survivingUnits,
            'published'         => !$logStatus->unitsReturning(),
        ]);

        $mapName = $defender->gameMap->name;
        $message = '';
        $type    = '';

        if ($logStatus->attackedKingdom()) {
            $message = 'You landed for  kingdom at: (X/Y) ' .
                $defender->x_position . '/' . $defender->y_position .
                ' on the ' . $mapName . ' plane, and is returning. Check the kingdom attack logs for more info.';

            $type = 'kingdom-attacked';
        }

        if ($logStatus->lostAttack()) {
            $message = 'You lost all your units when attacking kingdom at: (X/Y) ' .
                $defender->x_position . '/' . $defender->y_position .
                ' on the ' . $mapName . ' plane. Check the kingdom attack logs for more info.';

            $type = 'all-units-lost';
        }

        if ($logStatus->tookKingdom()) {
            $characterName = $defender->character->name;

            $message = 'You have taken ' . $characterName . '\'s kingdom at (X\Y) ' . $defender->x_position . '/' . $defender->y_position .
                ' on the ' . $mapName . ' plane. Any surviving units have been added to the kingdoms units. Check the kingdom attack logs for more info.';

            $type = 'kingdom-taken';
        }

        if ($logStatus->unitsReturning()) {
            $characterName = $defender->character->name;

            $message = 'Your units are retuning from ' . $characterName . '\'s kingdom at (X\Y) ' . $defender->x_position . '/' . $defender->y_position .
                ' on the ' . $mapName . ' plane. When they are back a log will be generated with details';

            $type = 'kingdom-taken';
        }

        $this->sendMessage($character->user, $type, $message);
    }

    /**
     * Send the message to the server.
     *
     * @param User $user
     * @param $type
     * @param string $message
     * @return array|void|null
     */
    protected function sendMessage(User $user, $type, string $message) {
        if (!UserOnlineValue::isOnline($user)) {
            $subjectParts = explode('-', $type);
            $subject      = ucfirst($subjectParts[0]) . ' ' . ucfirst($subjectParts[1]) . '!';

            return \Mail::to($user->email)->send(new GenericMail($user, $message, $subject));
        }

        return event(new KingdomServerMessageEvent($user, $type, $message));
    }

    /**
     * Broadcast a public message of the kingdom being taken.
     *
     * @param $defender
     * @param Character $character
     */
    protected function kingdomHasFallenMessage($defender, Character $character) {
        $message = $defender->character->name . '\'s kingdom on the ' . $defender->gameMap->name . ' plane, has fallen! ' . $character->name . ' is now the rightful ruler!';

        broadcast(new GlobalMessageEvent($message));
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
            $timeToReturn = now()->addMinutes($timeToReturn);

            $unitMovement->update([
                'units_moving' => [
                    'new_units' => $this->survivingUnits,
                    'old_units' => $this->unitsSent
                ],
                'completed_at' => $timeToReturn,
                'started_at' => now(),
                'moving_to_x' => $unitMovement->from_x,
                'moving_to_y' => $unitMovement->from_y,
                'from_x' => $unitMovement->moving_to_x,
                'from_y' => $unitMovement->moving_to_y,
            ]);

            $unitMovement = $unitMovement->refresh();

            MoveUnits::dispatch($unitMovement->id, $defender->id, 'return', $character)->delay(now()->addMinutes(2, /*$timeToReturn*/));
        }
    }

    protected function attemptToSettleKingdom(Kingdom $defender, UnitMovementQueue $unitMovement, Character $character) {
        if ($defender->current_morale > 0) {
            $defender = $this->kingdomHandler->updateDefendersMorale($defender, $this->settler);

            if ($defender->current_morale === 0 || $defender->current_morale === 0.0) {

                $this->kingdomHandler->takeKingdom($unitMovement, $character, $this->survivingUnits);

                $this->notifyDefender(KingdomLogStatusValue::LOST_KINGDOM, $defender->character, $defender);

                $this->notifyAttacker(KingdomLogStatusValue::TAKEN, $defender, $unitMovement, $character);

                $this->kingdomHasFallenMessage($defender, $character);
            } else {
                $this->newDefender = $defender->load('units')->toArray();

                $this->notifyDefender(KingdomLogStatusValue::KINGDOM_ATTACKED, $defender->character, $defender);

                $this->notifyAttacker(KingdomLogStatusValue::ATTACKED, $defender, $unitMovement, $character);

                $this->returnUnits($defender, $unitMovement, $character);
            }
        } else {
            $this->kingdomHandler->takeKingdom($unitMovement, $character, $this->survivingUnits);

            $this->notifyDefender(KingdomLogStatusValue::LOST_KINGDOM, $defender->character, $defender);

            $this->notifyAttacker(KingdomLogStatusValue::TAKEN, $defender, $unitMovement, $character);

            $this->kingdomHasFallenMessage($defender, $character);
        }
    }

    /**
     * Is the settler unit the only one left?
     *
     * @param array $attackingUnits
     * @return bool
     */
    protected function isSettlerTheOnlyUnitLeft(array $attackingUnits): bool {
        $allDead = false;

        foreach ($attackingUnits as $unitInfo) {
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
     * @param array $regularUnits
     * @return mixed|null
     */
    protected function findSettlerUnit(array $regularUnits) {
        if (empty($regularUnits)) {
            return null;
        }

        // If there is only one unit and it's a setler
        // Then it dies.
        if (count($regularUnits) === 1) {
            return null;
        }

        $settler = null;

        foreach ($regularUnits as $unitInfo) {
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
