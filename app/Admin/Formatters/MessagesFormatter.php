<?php

namespace App\Admin\Formatters;

use Illuminate\Support\Collection;

class MessagesFormatter {

    public function format(Collection $messages): Collection {
        return $messages->transform(function($message) {

            if ($message->user->hasRole('Admin')) {
                $characterName = 'Admin';
            } else {
                $characterName = $message->user->character->name;
            }

            $from = null;
            $to = null;

            if (!is_null($message->from_user)) {
                if ($message->user->hasRole('Admin')) {
                    $from = 'The Creator';
                } else {
                    $from = $message->fromUser->character->name;
                }
            }

            if (!is_null($message->to_user)) {
                if ($message->user->hasRole('Admin')) {
                    $to = 'The Creator';
                } else {
                    $to = $message->fromUser->character->name;
                }
            }

            $message->character_name     = $characterName;
            $message->from_character     = $from;
            $message->to_character       = $to;
            $message->is_private         = !is_null($message->from_user) && !is_null($message->to_user) ? 'Yes' : 'No';

            return $message;
        });
    }
}
