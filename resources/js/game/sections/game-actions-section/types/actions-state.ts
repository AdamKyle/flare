import MonsterType from "../../../lib/game/types/actions/monster/monster-type";
import PvpCharactersType from "../../../lib/game/types/pvp-characters-type";
import DuelData from "../../../lib/game/types/core/duel-player/definitions/duel-data";
import RaidMonsterType from "../../../lib/game/types/actions/monster/raid-monster-type";

export default interface ActionsState {
    monsters: MonsterType[] | [];

    raid_monsters: RaidMonsterType[] | [];

    characters_for_dueling: PvpCharactersType[] | [];

    pvp_characters_on_map: PvpCharactersType[] | [];

    attack_time_out: number;

    crafting_time_out: number;

    crafting_type: string | null;

    duel_fight_info: DuelData | null;

    loading: boolean;

    show_exploration: boolean;

    show_celestial_fight: boolean;

    show_duel_fight: boolean;

    show_join_pvp: boolean;

    show_hell_forged_section: boolean;

    show_purgatory_chains_section: boolean;

    show_twisted_earth_section: boolean;

    show_gambling_section: boolean;
}
