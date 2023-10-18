<?php

namespace App\Game\Battle\Services;


use App\Game\Maps\Events\UpdateMap;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Monster;
use App\Flare\Values\CelestialType;
use App\Flare\Builders\BuildMythicItem;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\MonthlyPvpParticipant;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class MonthlyPvpFightService {

    /**
     * @var int $counter
     */
    private int $counter = 0;

    /**
     * @var Collection $participants
     */
    private Collection $participants;

    /**
     * @var PvpService $pvpService
     */
    private PvpService $pvpService;

    /**
     * @var ConjureService $conjureService
     */
    private ConjureService $conjureService;

    /**
     * @var BuildMythicItem $buildMythicItem
     */
    private BuildMythicItem $buildMythicItem;

    /**
     * @param PvpService $pvpService
     * @param ConjureService $conjureService
     * @param BuildMythicItem $buildMythicItem
     */
    public function __construct(PvpService $pvpService, ConjureService $conjureService, BuildMythicItem $buildMythicItem) {
        $this->pvpService      = $pvpService;
        $this->conjureService  = $conjureService;
        $this->buildMythicItem = $buildMythicItem;
    }

    /**
     * Set the registered participants.
     *
     * @param Collection $participants
     * @return $this
     */
    public function setRegisteredParticipants(Collection $participants): MonthlyPvpFightService {
        $this->participants = $participants;

        return $this;
    }

    /**
     * Starts pvp.
     *
     * @return bool
     * @throws Exception
     */
    public function startPvp(): bool {
        if (count($this->participants) >= 2) {
            $participants = $this->reOrderCharactersByLevel($this->participants->pluck('id')->toArray());

            if ($participants->count() >= 2) {
                $this->fight($participants);

                if ($this->startPvp()) {
                    return true;
                }
            } else {
                $this->lastPlayerStanding($participants[0]->character);

                $this->conjureTheKings($participants[0]->character);

                MonthlyPvpParticipant::truncate();

                return true;
            }
        }

        event(new GlobalMessageEvent('Pvp canceled. Not enough logged in players.'));

        MonthlyPvpParticipant::truncate();

        return false;
    }

    /**
     * Conjure the celestial kings as public entities.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    protected function conjureTheKings(Character $character): void {
        event(new GlobalMessageEvent('The kings awaken. The ground rumbles!'));

        foreach (Monster::where('celestial_type', CelestialType::KING_CELESTIAL)->get() as $monster) {
            $this->conjureService->conjure($monster, $character, 'public');
        }

        event(new GlobalMessageEvent('Christ children of Tlessa! The Celestial Kings have escaped their prisons. Quick slaughter them for a chance at mythical items!'));
    }

    /**
     * Reward the last player standing.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    protected function lastPlayerStanding(Character $character) {
        event(new GlobalMessageEvent('Congratulation to: ' . $character->name . ' for winning this months pvp! The Creator smiles upon them with a beautiful [ MYTHIC ] gift!'));

        $item = $this->buildMythicItem->fetchMythicItem($character);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $item->id,
        ]);

        event(new ServerMessageEvent($character->user, 'You are rewarded with a Mythic Unique: ' . $item->affix_name . ' This item has been given to you regardless of how full your inventory is.', $item->id));

        $gold = $character->gold + 50000000000;

        if ($gold > MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->refresh()->update([
            'gold' => $gold,
            'can_move' => true,
            'can_attack' => true,
        ]);

        event(new ServerMessageEvent($character->user, 'Rewarded with : 50,000,000,000 Gold!'));

        CharacterAutomation::where('character_id', $character->id)->delete();

        $character = $character->refresh();

        event(new UpdateCharacterStatus($character));

        event(new UpdateTopBarEvent($character));

        event(new UpdateMap($character->user));

        event(new ServerMessageEvent($character->user, 'You can move from the location now. The arena is closed. Come again next month!'));
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
        $result = $this->pvpService->attack($attacker->character, $defender->character, $attacker->attack_type, true, true);

        $this->handleResult($defender, $attacker, $result);

        $this->counter++;
    }

    /**
     * Kick inactive player.
     *
     * @param Character $character
     * @return void
     */
    protected function kickCheacterWhoLoggedOut(Character $character) {
        $character->update([
            'can_move'   => true,
            'can_attack' => true,
        ]);

        CharacterAutomation::where('character_id', $character->id)->delete();

        MonthlyPvpParticipant::where('character_id', $character->id)->delete();

        $character = $character->refresh();

        event(new UpdateCharacterStatus($character));

        event(new UpdateMap($character->user));
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

        CharacterAutomation::where('character_id', $character->id)->delete();

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'You were rewarded with 2,000,000,000 Gold for participating :D'));

        event(new UpdateCharacterStatus($character));

        event(new UpdateMap($character->user));
    }

    /**
     * @param array $characterIds
     * @return Collection
     */
    protected function reOrderCharactersByLevel(array $characterIds): Collection {
        return MonthlyPvpParticipant::whereIn('character_id', $characterIds)->join('characters', function ($join) {
            $join->on('characters.id', '=', 'monthly_pvp_participants.character_id');
        })->orderBy('characters.level', 'asc')->select('monthly_pvp_participants.*')->get();
    }
}
