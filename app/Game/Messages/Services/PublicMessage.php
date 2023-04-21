<?php

namespace App\Game\Messages\Services;

use App\Admin\Events\UpdateAdminChatEvent;
use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Messages\Events\MessageSentEvent;
use App\Game\Messages\Values\MapChatColor;

class PublicMessage {

    /**
     * Post a public message.
     *
     * @param string $message
     * @return void
     */
    public function postPublicMessage(string $message): void {
        $user = auth()->user();

        $killedInPVP = $this->wasKilledInPVP($user);

        $newMessage = $user->messages()->create([
            'message'       => $message,
            'x_position'    => $killedInPVP ? 0 : $this->getXPosition($user),
            'y_position'    => $killedInPVP ? 0 : $this->getYPosition($user),
            'color'         => $this->getColor($user),
            'hide_location' => $this->hideLocation($user),
        ]);

        $newMessage->map_name = $this->shortenedMapName($user);

        broadcast(new MessageSentEvent($user, $newMessage))->toOthers();
    }

    /**
     * Get the X Position of the user.
     *
     * @param User $user
     * @return int
     */
    protected function getXPosition(User $user): int {

        if (!$user->hasRole('Admin')) {
            return $user->character->map->character_position_x;
        }

        return 0;
    }

    /**
     * Get the Y position of the user.
     *
     * @param User $user
     * @return int
     */
    protected function getYPosition(User $user): int {

        if (!$user->hasRole('Admin')) {
            return $user->character->map->character_position_y;
        }

        return 0;
    }

    /**
     * Was the user killed in PVP?
     *
     * @param User $user
     * @return bool
     */
    protected function wasKilledInPVP(User $user): bool {

        if (!$user->hasRole('Admin')) {
            return $user->character->killed_in_pvp;
        }

        return false;
    }

    /**
     * Get the shortened name of the map the player is on.
     *
     * @param User $user
     * @return string|null
     */
    protected function shortenedMapName(User $user): ?string {
        $user = auth()->user();

        if ($user->hasRole('Admin')) {
            return null;
        }

        $gameMapName = $user->character->map->gameMap->name;

        switch ($gameMapName) {
            case 'Labyrinth':
                return 'LABY';
            case 'Dungeons':
                return 'DUN';
            case 'Shadow Plane':
                return 'SHP';
            case 'Hell':
                return 'HELL';
            case 'Purgatory':
                return 'PURG';
            case 'Surface':
            default:
                return 'SUR';
        }
    }

    /**
     * Get the predefined hex code color of the message based on map name.
     *
     * @param User $user
     * @return string|null
     */
    protected function getColor(User $user): ?string {
        if ($user->hasRole('Admin')) {
            return null;
        }

        return (new MapChatColor($user->character->map->gameMap->name))->getColor();
    }

    protected function hideLocation(User $user): bool {
        if ($user->hasRole('Admin')) {
            return false;
        }

        return $user->character->inventory->slots->filter(function($slot) {
            return $slot->item->effect === ItemEffectsValue::HIDE_CHAT_LOCATION;
        })->isNotEmpty();
    }
}
