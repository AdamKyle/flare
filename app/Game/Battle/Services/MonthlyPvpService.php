<?php

namespace App\Game\Battle\Services;

use  Illuminate\Database\Eloquent\Builder;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Event;
use App\Flare\Models\MonthlyPvpParticipant;
use App\Flare\Values\EventType;
use App\Flare\Values\UserOnlineValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Maps\Events\UpdateMapBroadcast;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class MonthlyPvpService {

    public function moveParticipatingPlayers() {

        if (MonthlyPvpParticipant::all()->isEmpty()) {
            event(new GlobalMessageEvent('The Creator is sad, no one want to participate in monthly PVP :( Maybe next month! Shiny rewards yo!'));

            MonthlyPvpParticipant::truncate();

            Event::where('type', EventType::MONTHLY_PVP)->delete();

            return;
        }

        $query = (new UserOnlineValue())->getUsersOnlineQuery();

        if (!$this->doWeHaveEnoughUsersOnLine($query)) {
             return;
        }

        $users = $query->pluck('user_id')->toArray();

        $usersInFight = Users::whereIn('id', $users);

        if (!$this->doWeHaveEnoughRegisteredPlayersOnline($usersInFight)) {
            return;
        }

        event(new GlobalMessageEvent('ATTN! Monthly pvp is about to start! Moving all participants!'));

        $usersInFight->chunkById(100, function($users) {
           foreach ($users as $user) {
               $this->movePlayerToNewLocation($user->character);
           }
        });

        event(new GlobalMessageEvent('ATTN! Participants for monthly pvp have been moved. Now is the time to check your gear. Battle starts in 5 minutes!!!'));
    }

    /**
     * Are there enough players online to do this?
     *
     * @param Builder $query
     * @return bool
     */
    protected function doWeHaveEnoughUsersOnLine(Builder $query): bool {
        if ($query->count() < 2) {
            event(new GlobalMessageEvent('The monthly pvp event has been called off because of: Lack of players logged in. There must always be at least two people to participate. Better luck next month!'));

            MonthlyPvpParticipant::truncate();

            Event::where('type', EventType::MONTHLY_PVP)->delete();

            return false;
        }

        return true;
    }

    /**
     * Are enough of these online people registered?
     *
     * @param Builder $usersInFight
     * @return bool
     */
    protected function doWeHaveEnoughRegisteredPlayersOnline(Builder $usersInFight): bool {
        $usersInFight = $usersInFight->get()->filter(function($user) {
            return !is_null($user->character);
        });

        if ($usersInFight->count() < 2) {
            event(new GlobalMessageEvent('The monthly pvp event has been called off because of: Lack of players registered. There must always be at least two people to participate. Better luck next month!'));

            MonthlyPvpParticipant::truncate();

            Event::where('type', EventType::MONTHLY_PVP)->delete();

            return false;
        }

        return true;
    }

    /**
     * Move the player to the arena.
     *
     * @param Character $character
     * @return void
     */
    protected function movePlayerToNewLocation(Character $character) {
        $location = Location::were('can_players_enter', false)->first();

        $character->map()->update([
            'character_position_x' => $location->x,
            'character_position_y' => $location->y,
            'game_map_id'          => $location->game_map_id,
        ]);

        $character->update([
            'can_attack' => false,
            'can_move'   => false,
        ]);

        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character)->delay(now()->addSeconds(2));

        event(new UpdateMapBroadcast($character->user));

        event(new UpdateCharacterStatus($character));

        event(new ServerMessageEvent($character->user, 'You have been moved to the Arena! You have a moment to adjust your gear. You cannot move, cannot fight.'));
    }
}
