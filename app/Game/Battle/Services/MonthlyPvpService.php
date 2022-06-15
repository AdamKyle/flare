<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use  Illuminate\Database\Eloquent\Builder;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\MonthlyPvpParticipant;
use App\Flare\Values\UserOnlineValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Maps\Events\UpdateMapBroadcast;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Flare\Models\Location;

class MonthlyPvpService {

    private MonthlyPvpFightService $monthlyPvpFightService;

    public function __construct(MonthlyPvpFightService $monthlyPvpFightService) {
        $this->monthlyPvpFightService = $monthlyPvpFightService;
    }

    public function moveParticipatingPlayers() {

        if (MonthlyPvpParticipant::all()->isEmpty()) {
            event(new GlobalMessageEvent('The Creator is sad, no one want to participate in monthly PVP :( Maybe next month! Shiny rewards yo!'));

            MonthlyPvpParticipant::truncate();

            return;
        }

        $query = (new UserOnlineValue())->getUsersOnlineQuery();

        if (!$this->doWeHaveEnoughUsersOnLine($query)) {
             return;
        }

        $users = $query->pluck('user_id')->toArray();

        $usersInFight = User::whereIn('id', $users);

        if (!$this->doWeHaveEnoughRegisteredPlayersOnline($usersInFight)) {
            return;
        }

        event(new GlobalMessageEvent('ATTN! Monthly pvp is about to start! Moving all participants!'));

        $usersInFight->chunkById(100, function($users) {
           foreach ($users as $user) {
               $this->movePlayerToNewLocation($user->character);
           }
        });

        event(new GlobalMessageEvent('ATTN! Participants for monthly pvp have been moved. Battle is about to begin'));

        /**
         * TODO: This will be a job set to execute in 5 minutes.
         */
        $this->monthlyPvpFightService->setFirstRun()->startPvp();
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
    protected function movePlayerToNewLocation(Character $character): void {
        $location = Location::where('can_players_enter', false)->first();

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
