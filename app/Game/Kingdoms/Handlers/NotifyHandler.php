<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Events\KingdomServerMessageEvent;
use App\Flare\Jobs\SendOffEmail;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\Notification as Notification;
use App\Flare\Models\Npc;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Models\User;
use App\Flare\Values\KingdomLogStatusValue;
use App\Flare\Values\NpcTypes;
use App\Game\Core\Events\UpdateNotificationsBroadcastEvent;
use App\Game\Kingdoms\Events\UpdateKingdomLogs;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Flare\Values\UserOnlineValue;

class NotifyHandler {

    /**
     * @var Kingdom $attackingKingdom
     */
    private $attackingKingdom;

    /**
     * @var Kingdom $defendingKingdom
     */
    private $defendingKingdom;

    /**
     * @var Character $character
     */
    private $defendingCharacter;

    /**
     * @var array $oldDefendingKingdom
     */
    private $oldDefender;

    /**
     * @var array $newDefender
     */
    private $newDefender;

    /**
     * @var array $sentUnits
     */
    private $sentUnits;

    /**
     * @var array $survivingUnits
     */
    private $survivingUnits;

    /**
     * Set the attacking kingdom.
     *
     * @param Kingdom $attackingKingdom
     * @return NotifyHandler
     */
    public function setAttackingKingdom(Kingdom $attackingKingdom): NotifyHandler {
        $this->attackingKingdom = $attackingKingdom;

        return $this;
    }

    /**
     * set defending kingdom.
     *
     * @param Kingdom $defendingKingdom
     * @return $this
     */
    public function setDefendingKingdom(Kingdom  $defendingKingdom): NotifyHandler {
        $this->defendingKingdom = $defendingKingdom;

        return $this;
    }

    /**
     * Sets the defending character.
     *
     * @param Character|null $character
     * @return $this
     */
    public function setDefendingCharacter(Character $character = null): NotifyHandler {
        $this->defendingCharacter = $character;

        return $this;
    }

    /**
     * Sets the old defending kingdom.
     *
     * @param array $kingdom
     * @return $this
     */
    public function setOldDefendingKingdom(array $kingdom): NotifyHandler {
        $this->oldDefender = $kingdom;

        return $this;
    }

    /**
     * We need to re-find the kingdom that belongs to the defender and load its appropriate relations.
     *
     * @param Kingdom $kingdom
     * @return $this
     */
    public function setNewDefendingKingdom(Kingdom $kingdom): NotifyHandler {
        $this->newDefender = Kingdom::where('id', $kingdom->id)
                                    ->where('character_id', $kingdom->character_id)
                                    ->first()
                                    ->load('units', 'buildings')
                                    ->toArray();

        return $this;
    }

    /**
     * Sets the sent units.
     *
     * @param array $sent
     * @return $this
     */
    public function setSentUnits(array $sent): NotifyHandler {
        $this->sentUnits = $sent;

        return $this;
    }

    /**
     * Sets surviving Units.
     *
     * @param array $surviving
     * @return NotifyHandler
     */
    public function setSurvivingUnits(array $surviving): NotifyHandler {
        $this->survivingUnits = $surviving;

        return $this;
    }

    /**
     * Notify the defender of any messages relating to their kingdom.
     *
     * @param string $status
     * @param Kingdom $defender
     * @throws \Exception
     */
    public function notifyDefender(string $status, Kingdom $defender) {
        if (is_null($defender->character_id) || is_null($this->defendingCharacter)) {
            return;
        }

        $value = new KingdomLogStatusValue($status);

        $attackLog = KingdomLog::create([
            'character_id'    => $this->defendingCharacter->id,
            'status'          => $status,
            'to_kingdom_id'   => $this->defendingKingdom->id,
            'from_kingdom_id' => $this->attackingKingdom->id,
            'old_defender'    => $value->lostKingdom() ? [] : $this->oldDefender,
            'new_defender'    => $value->lostKingdom() ? [] : (is_null($this->newDefender) ? $defender : $this->newDefender),
            'units_sent'       => $this->sentUnits,
            'units_survived'  => $this->survivingUnits,
            'published'       => true,
        ]);

        $message = 'Your kingdom at (X/Y): ' . $defender->x_position .
        '/' . $defender->y_position . ' on the ' .
        $defender->gameMap->name . ' plane, was attacked.';

        $this->createNotificationEvent($this->defendingCharacter, $attackLog, $message, 'failed', 'Kingom Attacked!');

        $message = '';
        $type    = '';

        if ($value->kingdomWasAttacked()) {

            $message = 'Your kingdom ' . $defender->name . ' at (X/Y) ' . $defender->x_position .
                '/' . $defender->y_position . ' on the ' .
                $defender->gameMap->name . ' plane, was attacked. Check your attack logs for more info.';

            $type = 'kingdom-attacked';
        }

        if ($value->lostKingdom()) {
            $message = 'Your kingdom ' . $defender->name . ' at (X/Y) ' . $defender->x_position .
                '/' . $defender->y_position . ' on the ' .
                $defender->gameMap->name . ' plane, was taken. Check your attack logs for more info.';

            $type = 'kingdom-taken';
        }

        $this->sendMessage($this->defendingCharacter->user, $type, $message);
    }

    /**
     * Notify the attacker of any messages related to the attack.
     *
     * @param string $status
     * @param Kingdom $defender
     * @param Character $character
     */
    public function notifyAttacker(string $status, Kingdom $defender, Character $character) {
        $logStatus     = new KingdomLogStatusValue($status);
        $mapName       = $defender->gameMap->name;
        $characterName = Npc::where('type', NpcTypes::KINGDOM_HOLDER)->first()->real_name;

        if (!is_null($defender->character)) {
            $characterName = $defender->character->name;
        }

        if (!$logStatus->unitsReturning()) {

            $attackLog = KingdomLog::create([
                'character_id' => $character->id,
                'from_kingdom_id' => $this->attackingKingdom->id,
                'to_kingdom_id' => $this->defendingKingdom->id,
                'status' => $status,
                'units_sent' => $this->sentUnits,
                'units_survived' => $this->survivingUnits,
                'old_defender' => $this->oldDefender,
                'new_defender' => is_null($this->newDefender) ? $defender->toArray() : $this->newDefender,
                'published' => ($logStatus->lostAttack() || $logStatus->tookKingdom()),
            ]);

            if ($logStatus->lostAttack()) {
                $message = 'You lost all your units when attacking '.$defender->name.' at: (X/Y) ' .
                    $defender->x_position . '/' . $defender->y_position .
                    ' on the ' . $mapName . ' plane.';

                $this->createNotificationEvent($character, $attackLog, $message, 'failed', 'Lost attack!');
            }

            if ($logStatus->tookKingdom()) {
                $message = 'You have taken ' . $characterName . '\'s kingdom at (X\Y) ' . $defender->x_position . '/' . $defender->y_position .
                    ' on the ' . $mapName . ' plane.';

                $this->createNotificationEvent($character, $attackLog, $message, 'success', 'Took Kingdom!');
            }
        }


        $message = '';
        $type    = '';

        if ($logStatus->attackedKingdom()) {
            $message = 'You landed an attack on a kingdom at: (X/Y) ' .
                $defender->x_position . '/' . $defender->y_position .
                ' on the ' . $mapName . ' plane.';

            $type = 'kingdom-attacked';
        }

        if ($logStatus->lostAttack()) {
            $message = 'You lost all your units when attacking '.$defender->name.' at: (X/Y) ' .
                $defender->x_position . '/' . $defender->y_position .
                ' on the ' . $mapName . ' plane. Check the kingdom attack logs for more info.';

            $type = 'all-units-lost';
        }

        if ($logStatus->tookKingdom()) {
            $characterName = $defender->character->name;

            if ($this->oldDefender['npc_owned']) {
                $characterName = Npc::where('type', NpcTypes::KINGDOM_HOLDER)->first()->real_name;
            }

            $message = 'You have taken ' . $characterName . '\'s kingdom at (X\Y) ' . $defender->x_position . '/' . $defender->y_position .
                ' on the ' . $mapName . ' plane. Any surviving units have been added to the kingdoms units. Check the kingdom attack logs for more info.';

            $type = 'kingdom-taken';
        }

        if ($logStatus->unitsReturning()) {
            if (is_null($defender->character_id)) {
                $characterName = Npc::where('type', NpcTypes::KINGDOM_HOLDER)->first()->real_name;
            } else {
                $characterName = $defender->character->name;
            }


            $message = 'Your units are retuning from ' . $characterName . '\'s kingdom at (X\Y) ' . $defender->x_position . '/' . $defender->y_position .
                ' on the ' . $mapName . ' plane. When they are back a log will be generated with details';

            if (!is_null($defender->character_id)) {
                if ($defender->character->id === $character->id) {
                    $message = 'Your units are returning from: (X\Y) ' . $defender->x_position . '/' . $defender->y_position . '. You own this kingdom.';
                }
            }

            $type = 'units-returning';
        }

        $this->sendMessage($character->user, $type, $message);
    }

    /**
     * Broadcast a public message of the kingdom being taken.
     *
     * @param Character $character
     * @param string $gameMapName
     */
    public function kingdomHasFallenMessage(Character $character, string $gameMapName) {
        if (is_null($this->defendingCharacter)) {
            $defenderName = Npc::where('type', NpcTypes::KINGDOM_HOLDER)->first()->real_name;
        } else {
            $defenderName = $this->defendingCharacter->name;
        }

        $message = $defenderName. '\'s kingdom on the ' .
            $gameMapName . ' plane, has fallen! ' .
            $character->name . ' is now the rightful ruler!';

        broadcast(new GlobalMessageEvent($message));
    }

    /**
     * Send the message to the server.
     *
     * @param User $user
     * @param $type
     * @param string $message
     * @return array|void|null
     */
    public function sendMessage(User $user, $type, string $message) {
        if (!UserOnlineValue::isOnline($user) && $user->kingdom_attack_email) {
            $subjectParts = explode('-', $type);
            $subject      = ucfirst($subjectParts[0]) . ' ' . ucfirst($subjectParts[1]) . '!';

            SendOffEmail::dispatch($user, (new GenericMail($user, $message, $subject)))->delay(now()->addMinute());

            return;
        }

        return event(new KingdomServerMessageEvent($user, $type, $message));
    }

    /**
     * Create a notification event for the front end.
     *
     * @param Character $character
     * @param KingdomLog $log
     * @param string $message
     */
    public function createNotificationEvent(Character $character, KingdomLog $log, string $message, string $status = 'failed', string $title = '') {
        Notification::create([
            'character_id' => $character->id,
            'title'        => $title,
            'message'      => $message,
            'status'       => $status,
            'type'         => 'kingdom',
            'url'          => route('game.kingdom.attack-log', [
                'character'  => $character->id,
                'kingdomLog' => $log->id,
            ]),
        ]);

        event(new UpdateNotificationsBroadcastEvent($character->refresh()->notifications()->where('read', false)->get(), $character->user));

        event(new UpdateKingdomLogs($character->refresh()));
    }
}
