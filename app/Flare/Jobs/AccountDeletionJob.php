<?php

namespace App\Flare\Jobs;

use DB;
use Mail;
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
    public $user;

    public $systemDeletion;

    /**
     * Create a new job instance.
     *
     * @param User $user
     */
    public function __construct(User $user, bool $systemDeletion = false) {
        $this->user           = $user;
        $this->systemDeletion = $systemDeletion;
    }

    public function handle(CharacterDeletion $characterDeletion) {
        try {
            $user = $this->user;
            $characterName = $user->character->name;

            $characterDeletion->deleteCharacterFromUser($user->character);

            $siteAccessStatistic = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

            UserSiteAccessStatistics::create([
                'amount_signed_in' => $siteAccessStatistic->amount_signed_in - 1,
                'amount_registered' => $siteAccessStatistic->amount_registered - 1,
            ]);

            $adminUser = User::with('roles')->whereHas('roles', function ($q) {
                $q->where('name', 'Admin');
            })->first();

            broadcast(new UpdateSiteStatisticsChart($adminUser));

            if (!$this->systemDeletion) {
                Mail::to($user)->send(new GenericMail($user, 'You requested your account to be deleted. We have done so, this is your final confirmation email.', 'Account Deletion', true));

                $user->delete();

                event(new GlobalMessageEvent('The Creator is sad today: ' . $characterName . ' has decided to call it quits. We wish them the best on their journeys'));
            } else {
                $message = 'Hello, your account was deleted due to account inactivity.
                A player may only be inactive for 5 months at a time. You are of course welcome to come back at
                any time and start a new character.';

                Mail::to($user)->send(new GenericMail($user, $message, 'Automated Account Deletion', true));

                $user->delete();
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
