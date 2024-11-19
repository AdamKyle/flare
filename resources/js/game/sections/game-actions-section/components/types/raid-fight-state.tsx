import { BattleMessage } from "./battle-message-type";

export default interface RaidFightState {
    is_attacking: boolean;
    battle_messages: BattleMessage[] | [];
    character_current_health: number;
    monster_current_health: number;
    attacks_left: number;
    damage_dealt: number;
    error_message: string;
}
