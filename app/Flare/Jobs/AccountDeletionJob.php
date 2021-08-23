<?php

namespace App\Flare\Jobs;

use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\Skill;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Support\Collection;
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
    public function handle(KingdomResourcesService $kingdomResourcesService) {
        $user = $this->user;
        $characterName = $user->character->name;

        $this->emptyCharacterInventory($user->character->inventory);
        $this->emptyCharacterInventorysets($user->character->inventorySets);

        foreach ($user->character->kingdoms as $kingdom) {
            $kingdomResourcesService->setKingdom($kingdom)->giveNPCKingdoms(false);
        }

        $user->character->skills()->delete();

        $this->deleteCharacter($user->character);

        $siteAccessStatistic = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

        UserSiteAccessStatistics::create([
            'amount_signed_in'  => $siteAccessStatistic->ammount_signed_in - 1,
            'amount_registered' => $siteAccessStatistic->ammount_registered - 1,
        ]);

        $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

        broadcast(new UpdateSiteStatisticsChart($adminUser));

        Mail::to($user)->send(new GenericMail($user, 'Your account has been deleted', 'Account Deletion', true));

        $user->delete();

        event(new GlobalMessageEvent('The Creator is sad today: ' . $characterName . ' has decided to call it quits. We wish them the best on their journeys'));
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

        $character->adventureLogs()->delete();

        $character->notifications()->delete();

        $character->snapShots()->delete();

        $character->map->delete();

        $character->delete();
    }
}
