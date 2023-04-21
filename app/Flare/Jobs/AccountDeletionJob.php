<?php

namespace App\Flare\Jobs;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Services\CharacterDeletion;
use App\Flare\Models\User;
use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Messages\Events\GlobalMessageEvent;

class AccountDeletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UpdateMarketBoard;

    /**
     * @var User $user
     */
    protected User $user;

    /**
     * @var bool $emailUser
     */
    protected bool $emailUser;


    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param bool $emailUser
     */
    public function __construct(User $user, bool $emailUser = false) {
        $this->user      = $user;
        $this->emailUser = $emailUser;
    }

    /**
     * Delete the character and the associated user data.
     *
     * - Only email the user if they have manually deleted themselves.
     *
     * @param CharacterDeletion $characterDeletion
     * @return void
     */
    public function handle(CharacterDeletion $characterDeletion) {
        try {
            $user          = $this->user;
            $characterName = $user->character->name;

            $characterDeletion->deleteCharacterFromUser($user->character);

            $siteAccessStatistic = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

            UserSiteAccessStatistics::create([
                'amount_signed_in'  => $siteAccessStatistic->amount_signed_in - 1,
                'amount_registered' => $siteAccessStatistic->amount_registered - 1,
            ]);

            $adminUser = User::with('roles')->whereHas('roles', function ($q) {
                $q->where('name', 'Admin');
            })->first();

            broadcast(new UpdateSiteStatisticsChart($adminUser));

            if ($this->emailUser) {
                $message = 'You have deleted your account. This your confirmation email that all your data, email,
                password, character data and so on were deleted. I am sad to see you go and hope
                you come back in the future!';

                Mail::to($user->email)->send(new GenericMail($user, $message, 'Account Deletion', true));

                event(new GlobalMessageEvent('The Creator is sad today: ' . $characterName . ' has decided to call it quits. We wish them the best on their journeys'));
            }

            $user->delete();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
