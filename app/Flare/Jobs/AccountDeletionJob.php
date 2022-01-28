<?php

namespace App\Flare\Jobs;

use DB;
use Mail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;
use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Kingdoms\Service\KingdomResourcesService;
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

    public function handle(KingdomResourcesService $kingdomResourcesService) {
        try {
            $user = $this->user;
            $characterName = $user->character->name;

            if (!is_null($user->character->inventory)) {
                $this->emptyCharacterInventory($user->character->inventory);
            }

            if (!$user->character->inventorySets->isEmpty()) {
                $this->emptyCharacterInventorysets($user->character->inventorySets);
            }

            $this->deleteCharacterMarketListings($user->character);

            foreach ($user->character->kingdoms as $kingdom) {
                $kingdomResourcesService->setKingdom($kingdom)->giveNPCKingdoms(false, true);
            }

            $user->character->skills()->delete();

            $this->deleteCharacter($user->character);

            $siteAccessStatistic = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

            UserSiteAccessStatistics::create([
                'amount_signed_in' => $siteAccessStatistic->ammount_signed_in - 1,
                'amount_registered' => $siteAccessStatistic->ammount_registered - 1,
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

                // Mail::to($user)->send(new GenericMail($user, $message, 'Automated Account Deletion', true));

                $user->delete();
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }

    protected function deleteCharacterMarketListings(Character $character) {

        MarketBoard::where('character_id', $character->id)->chunkById(250, function($marketListings) {
            foreach ($marketListings as $marketListing) {
                $marketListing->delete();
            }
        });

        $this->sendUpdate(resolve(MarketItemsTransfromer::class), resolve(Manager::class), $character->user);
    }
    protected function emptyCharacterInventory(Inventory $inventory) {
        foreach ($inventory->slots as $slot) {
            $slot->delete();
        }

        $inventory->delete();
    }

    protected function emptyCharacterInventorySets(Collection $inventorySets) {
        foreach ($inventorySets as $set) {
            foreach ($set->slots as $slot) {
                $slot->delete();
            }

            $set->delete();
        }
    }


    protected function deleteCharacter(Character $character) {
        $character->skills()->delete();

        $character->kingdomAttackLogs()->delete();

        $character->adventureLogs()->delete();

        $character->unitMovementQueues()->delete();

        $character->boons()->delete();

        $character->questsCompleted()->delete();

        $character->notifications()->delete();

        $character->currentAutoMations()->delete();

        $character->factions()->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $character->passiveSkills()->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $character->map()->delete();

        $character->delete();
    }
}
