<?php

namespace App\Flare\Jobs;

use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Services\CharacterDeletion;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

class AccountDeletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UpdateMarketBoard;

    protected User $user;

    protected bool $emailUser;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, bool $emailUser = false)
    {
        $this->user = $user;
        $this->emailUser = $emailUser;
    }

    /**
     * Delete the character and the associated user data.
     *
     * - Only email the user if they have manually deleted themselves.
     *
     * @return void
     */
    public function handle(CharacterDeletion $characterDeletion)
    {
        $user = User::find($this->user->id);

        if (is_null($user)) {
            return;
        }

        $character = $user->character;
        $characterName = $character?->name;
        $userEmail = $user->email;
        $emailUser = $this->emailUser;

        if (! is_null($character)) {
            $characterDeletion->deleteCharacterFromUser($character);
        }

        $user->delete();

        try {
            $siteAccessStatistic = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

            if (! is_null($siteAccessStatistic)) {
                UserSiteAccessStatistics::create([
                    'amount_signed_in' => max(0, $siteAccessStatistic->amount_signed_in - 1),
                    'amount_registered' => max(0, $siteAccessStatistic->amount_registered - 1),
                ]);
            }

        } catch (Throwable $throwable) {
            Log::error('Account deletion site statistics update failed.', [
                'user_id' => $this->user->id,
                'exception' => $throwable,
            ]);
        }

        try {
            $adminUser = User::with('roles')->whereHas('roles', function ($query) {
                $query->where('name', 'Admin');
            })->first();

            broadcast(new UpdateSiteStatisticsChart($adminUser));
        } catch (Throwable $throwable) {
            Log::error('Account deletion site statistics broadcast failed.', [
                'user_id' => $this->user->id,
                'exception' => $throwable,
            ]);
        }

        if ($emailUser) {
            $message = 'You have deleted your account. This your confirmation email that all your data, email,
                password, character data and so on were deleted. I am sad to see you go and hope
                you come back in the future!';

            try {
                Mail::to($userEmail)->send(new GenericMail($user, $message, 'Account Deletion', true));
            } catch (Throwable $throwable) {
                Log::error('Account deletion confirmation email failed.', [
                    'user_id' => $this->user->id,
                    'exception' => $throwable,
                ]);
            }

            if (! is_null($characterName)) {
                try {
                    event(new GlobalMessageEvent('The Creator is sad today: '.$characterName.' has decided to call it quits. We wish them the best on their journeys'));
                } catch (Throwable $throwable) {
                    Log::error('Account deletion global message failed.', [
                        'user_id' => $this->user->id,
                        'character_name' => $characterName,
                        'exception' => $throwable,
                    ]);
                }
            }
        }
    }
}
