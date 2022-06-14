import {CharacterType} from "../../character/character-type";
import MonsterType from "./monster/monster-type";
import PvpCharactersType from "../pvp-characters-type";
import DuelType from "./duel-type";
import {CraftingOptions} from "./crafting-type-options";

export default interface ActionsState {

    character: CharacterType|null;

    monsters: MonsterType[];

    monster_to_fight: MonsterType|null;

    loading: boolean;

    is_same_monster: boolean;

    attack_time_out: number;

    crafting_time_out: number;

    character_revived: boolean;

    crafting_type: CraftingOptions;

    show_join_pvp?: boolean;

    show_exploration?: boolean;

    show_celestial_fight?: boolean;

    duel_characters?: PvpCharactersType[] | [];

    characters_for_dueling?: PvpCharactersType[] | [];

    show_duel_fight?: boolean;

    duel_fight_info?: DuelType | null;

    character_position?: {x: number; y: number, game_map_id?: number} | null;

    /**
     * These are for Smaller Actions Component.
     */

    selected_action?: string|null;

    movement_time_out?: number;

    can_player_move?: boolean;

    automation_time_out?: number;
}
