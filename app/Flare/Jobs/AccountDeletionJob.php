<?php

namespace App\Flare\Jobs;

use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use League\Fractal\Manager;
use Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class AccountDeletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User $user
     */
    public $user;


    /**
     * Create a new job instance.
     *
     * @param string $token
     */
    public function __construct(User $user) {
        $this->user     = $user;
    }

    /**
     * Processes the type of simmulation test we want.
     *
     * @return void
     */
    public function handle() {
        $user = $this->user;

        $this->emptyCharacterInventory($user->character->inventory);
        $this->deleteCharacter($user->character);
        $this->removeSecurityQuestions($user);

        $siteAccessStatistic = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

        UserSiteAccessStatistics::create([
            'amount_signed_in'  => $siteAccessStatistic->ammount_signed_in - 1,
            'amount_registered' => $siteAccessStatistic->ammount_registered - 1,
        ]);

        $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

        broadcast(new UpdateSiteStatisticsChart($adminUser));

        Mail::to($user)->send(new GenericMail($user, 'Your account has been deleted', 'Account Deletion', true));

        $user->delete();
    }

    protected function emptyCharacterInventory(Inventory $inventory) {
        foreach ($inventory->slots as $slot) {
            $marketListing = MarketBoard::where('item_id', $slot->item_id)->where('character_id', $inventory->character->id)->first();

            if (!is_null($marketListing)) {
                $marketListing->delete();

                $this->sendUpdate(resolve(MarketItemsTransfromer::class), resolve(Manager::class));
            }

            $slot->delete();
        }

        $inventory->delete();
    }

    protected function deleteCharacter(Character $character) {
        foreach ($character->skills as $skill) {
            $skill->delete();
        }

        foreach ($character->adventureLogs as $log) {
            $log->delete();
        }

        foreach ($character->notifications as $notification) {
            $notification->delete();
        }

        foreach ($character->snapShots as $snapShot) {
            $snapShot->delete();
        }

        $character->map->delete();

        foreach($character->kingdoms as $kingdom) {
            $kingdom->update([
                'character_id'   => null,
                'npc_owned'      => true,
                'current_morale' => 0.10
            ]);

            broadcast(new UpdateNPCKingdoms($kingdom->gameMap));
            broadcast(new UpdateGlobalMap($character));
        }

        $character->delete();
    }

    protected function removeSecurityQuestions(User $user) {
        $user->securityQuestions()->delete();
    }
}
