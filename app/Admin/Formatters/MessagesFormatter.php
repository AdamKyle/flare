<?php

namespace App\Admin\Formatters;

use Illuminate\Support\Collection;

class MessagesFormatter {

    public function format(Collection $messages): Collection {
        return $messages->transform(function($message) {

            $character = $message->user->character;

            if ($message->user->hasRole('Admin')) {
                $characterName = 'Admin';
            } else if (is_null($character->name)) {
               $characterName = 'Unknown';
            } else {
                $characterName = $character->name;
            }

            $message->character_name     = $characterName;
            $message->from_character     = !is_null($message->from_user) ? $message->fromUser->character->name : null;
            $message->to_character       = !is_null($message->to_user) ? $message->toUser->character->name : null;
            $message->is_private         = !is_null($message->from_user) && !is_null($message->to_user) ? 'Yes' : 'No';
            $message->forced_name_change = !$message->user->hasRole('Admin') ? $message->user->character->force_name_change : false;

            return $message;
        });
    }
}
