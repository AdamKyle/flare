<?php

namespace App\Game\Messages\Services;

use App\Flare\Models\User;
use App\Flare\Models\Announcement;
use App\Flare\Values\NameTags;
use App\Game\Messages\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class FetchMessages {

    /**
     * Fetch all messages from the previous 24 hours.
     *
     * @return SupportCollection
     */
    public function fetchMessages(): SupportCollection {
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
     *
     * @param Collection $messages
     * @return SupportCollection
     */
    protected function transformMessages(Collection $messages): SupportCollection {
        return $messages->transform(function ($message) {

            $message->x     = $message->x_position;
            $message->y     = $message->y_position;

            if (!is_null($message->color)) {
                $message->map = $this->getMapNameFromColor($message->color);
            }

            $message = $this->setUpCustomOverRides($message);

            return $this->setMessageName($message);
        });
    }

    /**
     * Set the name of the person who sent the message.
     *
     * @param Message $message
     * @return Message
     */
    protected function setMessageName(Message $message): Message {

        $user = $message->user;

        if ($user->hasRole('Admin')) {
            $message->name = 'The Creator';

            return $message;
        }

        $nameTag = $message->user->name_tag;

        $message->name     = $message->user->character->name;
        $message->name_tag = is_null($nameTag) ? null :NameTags::$valueNames[$nameTag];

        return $message;
    }

    protected function setUpCustomOverRides(Message $message): Message {

        $message->custom_class   = $message->user->chat_text_color;
        $message->is_chat_bold   = $message->user->chat_is_bold;
        $message->is_chat_italic = $message->user->chat_is_italic;

        return $message;
    }

    /**
     * Get the map name from the color on the message.
     *
     * @param string $color
     * @return string
     */
    protected function getMapNameFromColor(string $color): string {
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
