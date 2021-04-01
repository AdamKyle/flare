<?php

namespace App\Admin\Services;

use Mail;
use App\Flare\Models\User;
use App\Admin\Events\BannedUserEvent;
use App\Admin\Jobs\UpdateBannedUserJob;
use App\Flare\Mail\GenericMail;
use App\Game\Messages\Events\MessageSentEvent;


class UserService {

    /**
     * Fetch the unban at value.
     * 
     * We also schedule a job to unban them at a specific period of time.
     * 
     * `$for` can be `one-day` or `one-week`
     * 
     * @param User $user
     * @param string $for
     * @return mixed null | Carbon
     */
    public function fetchUnBanAt(User $user, string $for) {
        $unBanAt = null;

        switch($for) {
            case 'one-day':
                $unBanAt = now()->addDays(1);
                UpdateBannedUserJob::dispatch($user)->delay($unBanAt);
                break;
            case 'one-week':
                $unBanAt = now()->addWeeks(1);
                UpdateBannedUserJob::dispatch($user)->delay($unBanAt);
                break;
        }

        return $unBanAt;
    }

    /**
     * When a user gets banned perm we broad cast a message for all to see.
     * 
     * @param User $user
     * @return void
     */
    public function broadCastAdminMessage(User $user): void {
        $message = $user->character->name . ' Sees the sky open and lightening comes hurtling down, striking the earth - cracking the air for miles around! They have been smitten by the hand of The Creator!';

        $message = auth()->user()->messages()->create([
            'message' => $message,
        ]);
        
        broadcast(new MessageSentEvent(auth()->user(), $message))->toOthers();
    }

    /**
     * Send the banned mail to the user.
     * 
     * This allerts the user they have been banned.
     * 
     * @param user $user
     * @param Carbon | null $unBanAt
     * @return void
     */
    public function sendUserMail(User $user, $unBanAt): void {
        event(new BannedUserEvent($user));

        $unBannedAt = !is_null($unBanAt) ? $unBanAt->format('l jS \\of F Y h:i:s A') . ' ' . $unBanAt->timezoneName . '.' : 'For ever.';
        $message    = 'You have been banned until: ' . $unBannedAt . ' For the reason of: ' . $user->banned_reason;
        
        Mail::to($user->email)->send(new GenericMail($user, $message, 'You have been banned!', true));
    }
}