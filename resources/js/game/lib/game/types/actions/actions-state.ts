import {CharacterType} from "../../character/character-type";
import MonsterType from "./monster/monster-type";

export default interface ActionsState {

    character: CharacterType|null;

    monsters: MonsterType[];

    monster_to_fight: MonsterType|null;

    loading: boolean;

    is_same_monster: boolean;

    attack_time_out: number;

    crafting_time_out: number;

    character_revived: boolean;

    crafting_type: 'craft' | 'enchant' | 'alchemy' | 'workbench' | 'trinketry' | null;

    show_exploration?: boolean;

    show_celestial_fight?: boolean;

    duel_characters?: {id: number, name: string}[] | [];

    show_duel_fight?: boolean;

    /**
     * These are for Smaller Actions Component.
     */

    selected_action?: string|null;

    movement_time_out?: number;

    can_player_move?: boolean;

    automation_time_out?: number;
}
