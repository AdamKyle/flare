<?php

namespace App\Game\Messages\Services;

use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Database\Eloquent\Collection;
use App\Game\Messages\Models\Message;

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
        return $messages->transform(function($message) {

            $message->x     = $message->x_position;
            $message->y     = $message->y_position;
            $message->map   = $this->getMapNameFromColor($message->color);

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

        if ($user->hasRole('admin')) {
            $message->name = 'Admin';

            return $message;
        }

        $message->name = $message->user->character->name;


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
            case '#ababab':
                return 'SHP';
            case '#639cff':
                return 'PURG';
            case '#ffffff':
            default:
                return'SUR';
        }
    }
}
