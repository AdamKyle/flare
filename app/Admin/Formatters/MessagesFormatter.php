<?php

namespace App\Admin\Formatters;

use Illuminate\Support\Collection;

class MessagesFormatter
{
    public function format(Collection $messages): Collection
    {
        return $messages->transform(function ($message) {

            if (is_null($message->user) || is_null($message->user->character)) {
                $characterName = 'Unknown';
            } elseif ($message->user->hasRole('Admin')) {

                $characterName = 'Admin';
            } else {
                $characterName = $message->user->character->name;
            }

            if (is_null($message->to_user) || is_null($message->toUser->character)) {
                $toUser = 'Unknown';
            } elseif ($message->toUser->hasRole('Admin')) {
                $toUser = 'Admin';
            } else {
                $toUser = $message->toUser->character->name;
            }

            if (is_null($message->from_user) || is_null($message->fromUser->character)) {
                $fromUser = 'Unknown';
            } elseif ($message->fromUser->hasRole('Admin')) {
                $fromUser = 'Admin';
            } else {
                $fromUser = $message->fromUser->character->name;
            }

            $message->character_name = $characterName;
            $message->from_character = ! is_null($message->from_user) ? $fromUser : null;
            $message->to_character = ! is_null($message->to_user) ? $toUser : null;
            $message->is_private = ! is_null($message->from_user) && ! is_null($message->to_user) ? 'Yes' : 'No';

            return $message;
        });
    }
}
