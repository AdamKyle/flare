<?php

namespace App\Game\Messages\Services;

use App\Flare\Values\NameTags;
use App\Game\Messages\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class FetchMessages
{
    /**
     * Fetch all messages from the previous 24 hours.
     */
    public function fetchMessages(): SupportCollection
    {
        $messages = Message::with(['user', 'user.roles', 'user.character'])
            ->where('from_user', null)
            ->where('to_user', null)
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->take(1000)
            ->get();

        return $this->transformMessages($messages);
    }

    /**
     * Transform the messages.
     */
    protected function transformMessages(Collection $messages): SupportCollection
    {
        return $messages->transform(function ($message) {

            $message->x = $message->x_position;
            $message->y = $message->y_position;

            if (! is_null($message->color)) {
                $message->map = $this->getMapNameFromColor($message->color);
            }

            $message = $this->setUpCustomOverRides($message);

            return $this->setMessageName($message);
        });
    }

    /**
     * Set the name of the person who sent the message.
     */
    protected function setMessageName(Message $message): Message
    {

        $user = $message->user;

        if (is_null($user)) {
            $message->name = 'Deleted Character';
            $message->name_tag = null;

            return $message;
        }

        if ($user->hasRole('Admin')) {
            $message->name = 'The Creator';

            return $message;
        }

        if (is_null($user->character)) {
            $message->name = 'Deleted Character';
            $message->name_tag = null;

            return $message;
        }

        $nameTag = $user->name_tag;

        $message->name = $user->character->name;
        $message->name_tag = is_null($nameTag) ? null : NameTags::$valueNames[$nameTag];

        return $message;
    }

    protected function setUpCustomOverRides(Message $message): Message
    {
        $user = $message->user;

        if (is_null($user)) {
            $message->custom_class = null;
            $message->is_chat_bold = false;
            $message->is_chat_italic = false;

            return $message;
        }

        $message->custom_class = $user->chat_text_color;
        $message->is_chat_bold = $user->chat_is_bold;
        $message->is_chat_italic = $user->chat_is_italic;

        return $message;
    }

    /**
     * Get the map name from the color on the message.
     */
    protected function getMapNameFromColor(string $color): string
    {
        switch ($color) {
            case '#ffad47':
                return 'LABY';
            case '#ccb9a5':
                return 'DUN';
            case '#ff7d8e':
                return 'HELL';
            case '#ababab':
                return 'SHP';
            case '#639cff':
                return 'PURG';
            case '#aeb6d3':
                return 'ICE';
            case '#ffffff':
            default:
                return 'SUR';
        }
    }
}
