import MonsterType from "../../../../lib/game/types/actions/monster/monster-type";
import { BattleMessage } from "./battle-message-type";

export default interface FightSectionState {
    battle_messages: BattleMessage[] | [];

    character_current_health: number | undefined;

    character_max_health: number | undefined;

    monster_current_health: number;

    monster_max_health: number;

    monster_to_fight_id: number;

    is_character_voided: boolean;

    is_monster_voided: boolean;

    monster_to_fight: MonsterType | null;

    processing_rank_battle: boolean;

    setting_up_rank_fight: boolean;

    setting_up_regular_fight: boolean;

    processing_regular_fight: boolean;

    show_clear_message: boolean;

    error_message: string;

    open_elemental_atonement: boolean;
}
