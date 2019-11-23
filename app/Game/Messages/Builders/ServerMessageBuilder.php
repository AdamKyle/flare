<?php

namespace App\Game\Messages\Builders;

class ServerMessageBuilder {

    public function build(string $type): string {
        switch ($type) {
            case 'message_length_0':
                return 'Your message cannot be empty.';
            case 'message_to_max':
                return 'Your message is too long.';
            case 'invalid_command':
                return 'Command not recognized.';
            case 'no_matching_user':
                return 'Could not find a user with that name to private message.';
            default:
                return '';
        }
    }
}
