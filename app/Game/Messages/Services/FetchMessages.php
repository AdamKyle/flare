<?php

namespace App\Game\Messages\Services;

use App\Game\Character\Values\NameTag;
use App\Game\Messages\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class FetchMessages
{
    /**
     * Fetch the public chat messages from the previous 24 hours for the game chat.
     *
     * @return SupportCollection
     */
    public function fetchMessages(): SupportCollection
    {
        return $this->fetchPublicMessagesSince(now()->subDay(), 1000);
    }

    /**
     * Fetch every public chat message from the previous 30 days for the Admin chat.
     *
     * @return SupportCollection
     */
    public function fetchAdminMessages(): SupportCollection
    {
        return $this->fetchPublicMessagesSince(now()->subDays(30), null);
    }

    /**
     * Fetch and transform the public chat messages created since the given time, newest first.
     *
     * @param Carbon $since
     * @param ?int $limit
     * @return SupportCollection
     */
    private function fetchPublicMessagesSince(Carbon $since, ?int $limit): SupportCollection
    {
        $messages = Message::with(['user', 'user.roles', 'user.character'])
            ->whereNull('from_user')
            ->whereNull('to_user')
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->when(! is_null($limit), function (Builder $query) use ($limit) {
                $query->take($limit);
            })
            ->get();

        return $this->transformMessages($messages);
    }

    /**
     * Transform the persisted messages into the public chat message shape.
     *
     * @param Collection $messages
     * @return SupportCollection
     */
    private function transformMessages(Collection $messages): SupportCollection
    {
        return $messages->transform(function (Message $message) {

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
     * Set the display name and name tag of the user who sent the message.
     *
     * @param Message $message
     * @return Message
     */
    private function setMessageName(Message $message): Message
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
        $message->name_tag = is_null($nameTag) ? null : NameTag::from($nameTag)->label();

        return $message;
    }

    /**
     * Apply the sender's chat color, bold and italic cosmetics to the message.
     *
     * @param Message $message
     * @return Message
     */
    private function setUpCustomOverRides(Message $message): Message
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
     * Resolve the short map label from the map color persisted on the message.
     *
     * @param string $color
     * @return string
     */
    private function getMapNameFromColor(string $color): string
    {
        return match ($color) {
            '#ffad47' => 'LABY',
            '#ccb9a5' => 'DUN',
            '#ff7d8e' => 'HELL',
            '#ababab' => 'SHP',
            '#639cff' => 'PURG',
            '#aeb6d3' => 'ICE',
            default => 'SUR',
        };
    }
}
