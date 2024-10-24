<?php

namespace App\Game\Messages\Services;

use App\Flare\Models\User;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Messages\Events\MessageSentEvent;
use App\Game\Messages\Models\Message;
use App\Game\Messages\Values\MapChatColor;

class PublicMessage
{
    /**
     * Post a public message.
     */
    public function postPublicMessage(string $message): void
    {
        $user = auth()->user();

        $newMessage = $user->messages()->create([
            'message' => $message,
            'x_position' => $this->getXPosition($user),
            'y_position' => $this->getYPosition($user),
            'color' => $this->getColor($user),
            'hide_location' => $this->hideLocation($user),
        ]);

        $newMessage->map_name = $this->shortenedMapName($user);

        $newMessage = $this->setUpCustomOverRides($user, $newMessage);

        broadcast(new MessageSentEvent($user, $newMessage))->toOthers();
    }

    /**
     * Set the custom over rides on the text
     */
    protected function setUpCustomOverRides(User $user, Message $message): Message
    {

        $message->custom_class = $user->chat_text_color;
        $message->is_chat_bold = $user->chat_is_bold;
        $message->is_chat_italic = $user->chat_is_italic;

        return $message;
    }

    /**
     * Get the X Position of the user.
     */
    protected function getXPosition(User $user): int
    {

        if (! $user->hasRole('Admin')) {
            return $user->character->map->character_position_x;
        }

        return 0;
    }

    /**
     * Get the Y position of the user.
     */
    protected function getYPosition(User $user): int
    {

        if (! $user->hasRole('Admin')) {
            return $user->character->map->character_position_y;
        }

        return 0;
    }

    /**
     * Get the shortened name of the map the player is on.
     */
    protected function shortenedMapName(User $user): ?string
    {
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
            case 'The Ice Plane':
                return 'ICE';
            case 'Twisted Memories':
                return 'TWM';
            case 'Delusional Memories':
                return 'DM';
            case 'Surface':
            default:
                return 'SUR';
        }
    }

    /**
     * Get the predefined hex code color of the message based on map name.
     */
    protected function getColor(User $user): ?string
    {
        if ($user->hasRole('Admin')) {
            return null;
        }

        return (new MapChatColor($user->character->map->gameMap->name))->getColor();
    }

    protected function hideLocation(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return false;
        }

        return $user->character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::HIDE_CHAT_LOCATION;
        })->isNotEmpty();
    }
}
