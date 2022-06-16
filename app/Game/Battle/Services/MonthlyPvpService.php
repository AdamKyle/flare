<?php

namespace App\Game\Battle\Services;

use App\Admin\Events\RefreshUserScreenEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AutomationType;
use App\Game\Battle\Jobs\MonthlyPvpFight;
use  Illuminate\Database\Eloquent\Builder;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\MonthlyPvpParticipant;
use App\Flare\Values\UserOnlineValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Flare\Models\Location;

class MonthlyPvpService {

    /**
     * Move all participating players.
     *
     * @return void
     */
    public function moveParticipatingPlayers(): void {

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

        $usersInFight = Character::whereIn('user_id', $users)->join('monthly_pvp_participants', function($join) {
            $join->on('monthly_pvp_participants.character_id', '=', 'characters.id');
        })->select('characters.*');

        if (!$this->doWeHaveEnoughRegisteredPlayersOnline($usersInFight)) {
            return;
        }

        event(new GlobalMessageEvent('ATTN! Monthly pvp is about to start! Moving all participants!'));

        $usersInFight->chunkById(100, function($characters) {
           foreach ($characters as $character) {
               $this->movePlayerToNewLocation($character);
           }
        });

        event(new GlobalMessageEvent('ATTN! Participants for monthly pvp have been moved. Battle is about to begin in 2 minutes. Please stand by!'));

        MonthlyPvpFight::dispatch($usersInFight->get())->delay(now()->addMinutes(2));
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

        CharacterAutomation::create([
            'character_id'                   => $character->id,
            'type'                           => AutomationType::PVP_MONTHLY,
            'started_at'                     => now(),
            'completed_at'                   => now()->addMinutes(60),
        ]);

        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character)->delay(now()->addSeconds(2));


        event(new ServerMessageEvent($character->user, 'You have been moved to the Arena! You have a moment to adjust your gear. You are considered to be in Automation'));

        event(new RefreshUserScreenEvent($character->user));
    }
}
