import MonsterType from "./monster/monster-type";
import PvpCharactersType from "../pvp-characters-type";
import DuelType from "./duel-type";

export default interface ActionsState {
    monsters: MonsterType[]|[];

    characters_for_dueling: PvpCharactersType[]|[];

    attack_time_out: number;

    crafting_time_out: number;

    crafting_type: string|null;

    duel_fight_info: DuelType | null;

    loading: boolean;

    show_exploration: boolean;

    show_celestial_fight: boolean;

    show_duel_fight: boolean;

    show_join_pvp: boolean;
}
