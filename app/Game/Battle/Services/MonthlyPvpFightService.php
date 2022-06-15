<?php

namespace App\Game\Battle\Services;


use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\MonthlyPvpParticipant;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Flare\Values\UserOnlineValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Maps\Events\UpdateMapBroadcast;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Database\Eloquent\Collection;

class MonthlyPvpFightService {

    /**
     * @var int $counter
     */
    private int $counter = 0;

    /**
     * @var PvpService $pvpService
     */
    private PvpService $pvpService;

    /**
     * @var RandomAffixGenerator $randomAffixGenerator
     */
    private RandomAffixGenerator $randomAffixGenerator;

    /**
     * @param PvpService $pvpService
     * @param RandomAffixGenerator $randomAffixGenerator
     */
    public function __construct(PvpService $pvpService, RandomAffixGenerator $randomAffixGenerator) {
        $this->pvpService           = $pvpService;
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    /**
     * Sets the flag for the first run through.
     *
     * @return $this
     */
    public function setFirstRun(): MonthlyPvpFightService {
        $this->firstRun = true;

        return $this;
    }

    /**
     * Starts pvp.
     *
     * @return bool
     * @throws \Exception
     */
    public function startPvp(): bool {
        $userIds = $this->gatherEligiblePlayers();

        if (count($userIds) >= 2) {
            $participants = $this->reOrderCharactersByLevel($userIds);

            if ($participants->count() >= 2) {
                $this->fight($participants);

                if ($this->startPvp()) {
                    return true;
                }
            } else {
                $this->lastPlayerStanding($participants[0]->character);

                MonthlyPvpParticipant::truncate();

                return true;
            }
        }

        event(new GlobalMessageEvent('Pvp canceled. Not enough logged in players.'));

        MonthlyPvpParticipant::truncate();

        return false;
    }

    /**
     * Reward the last player standing.
     *
     * @param Character $character
     * @return void
     * @throws \Exception
     */
    protected function lastPlayerStanding(Character $character) {
        event(new GlobalMessageEvent('Congratulation to: ' . $character->name . ' for winning this months pvp! The Creator smiles upon them with a beautiful [ MYTHIC ] gift!'));

        $item = $this->fetchMythicItem($character);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $item->id,
        ]);

        $gold = $character->gold + 50000000000;

        if ($gold > MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->refresh()->update([
            'gold' => $gold,
            'can_move' => true,
            'can_attack' => true,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterStatus($character));

        event(new UpdateTopBarEvent($character));

        event(new UpdateMapBroadcast($character->user));

        event(new ServerMessageEvent($character->user,'You are rewarded with a Mythic Unique: ' . $item->affix_name . ' This item has been given to you regardless of how full your inventory is.', $slot->id));

        event(new ServerMessageEvent($character->user,'Rewarded with : 50,000,000 Gold!'));

        event(new ServerMessageEvent($character->user,'You can move from the location now. The arena is closed. Come again next month!'));
    }

    /**
     * Build Mythic Item for winner.
     *
     * @param Character $character
     * @return Item
     * @throws \Exception
     */
    private function fetchMythicItem(Character $character): Item {
        $prefix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::MYTHIC)
            ->generateAffix('prefix');

        $suffix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::MYTHIC)
            ->generateAffix('suffix');

        $item = Item::inRandomOrder()->first();

        $item = $item->duplicate();

        $item->update([
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
            'is_mythic'      => true,
        ]);

        return $item->refresh();
    }

    /**
     * Determines the two players to fight.
     *
     * @param Collection $participants
     * @return void
     */
    protected function fight(Collection $participants) {
        $attacker = $participants[0];
        $defender = $participants[1];

        $result = $this->pvpService->attack($attacker->character, $defender->character, $attacker->attack_type, false, true);

        $this->handleResult($defender, $attacker, $result);
    }

    /**
     * Handle primary fight.
     *
     * Can repeat the fight till one or both is dead.
     *
     * @param MonthlyPvpParticipant $defender
     * @param MonthlyPvpParticipant $attacker
     * @param bool $result
     * @return void
     */
    protected function handleResult(MonthlyPvpParticipant $defender, MonthlyPvpParticipant $attacker, bool $result) {
        if ($result) {
            $this->updateLosersCharacterStatus($defender->character);
        } else {
            $this->repeatFight($defender, $attacker);
        }
    }

    /**
     * Repeat the fight but with the defender as the attacker.
     *
     * @param MonthlyPvpParticipant $attacker
     * @param MonthlyPvpParticipant $defender
     * @return void
     */
    protected function repeatFight(MonthlyPvpParticipant $attacker, MonthlyPvpParticipant $defender) {
        if ($this->counter >= 10) {
            dump('Counter hit: ' . $this->counter);
        }

        $result = $this->pvpService->attack($attacker->character, $defender->character, $attacker->attack_type, true, true);

        $this->handleResult($defender, $attacker, $result);

        $this->counter++;
    }

    /**
     * Mark the loser as can attack and move.
     *
     * @param Character $character
     * @return void
     */
    protected function updateLosersCharacterStatus(Character $character) {
        $character->update([
            'can_move'   => true,
            'can_attack' => true,
        ]);

        $character = $character->refresh();

        MonthlyPvpParticipant::where('character_id', $character->id)->delete();

        event(new ServerMessageEvent($character->user, 'You were kicked out of monthly pvp because you lost. Don\'t worry, I have something for you :D'));

        $gold = $character->gold + 2000000000;

        if ($gold > MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $gold
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'You were rewarded with 2,000,000,000 Gold for participating :D'));

        event(new UpdateCharacterStatus($character));

        event(new UpdateMapBroadcast($character->user));
    }

    /**
     * @param array $userIds
     * @return Collection
     */
    protected function reOrderCharactersByLevel(array $userIds): Collection {

        $characterIds = Character::whereIn('user_id', $userIds)->pluck('id');

        return MonthlyPvpParticipant::whereIn('character_id', $characterIds)->join('characters', function($join) {
            $join->on('characters.id', '=', 'monthly_pvp_participants.character_id');
        })->orderBy('characters.level', 'asc')->select('monthly_pvp_participants.*')->get();
    }

    /**
     * @return array
     */
    protected function gatherEligiblePlayers(): array {
        $query = (new UserOnlineValue())->getUsersOnlineQuery();

        if ($query->count() >= 2) {
            return $query->pluck('user_id')->toArray();
        }

        return [];
    }
}
