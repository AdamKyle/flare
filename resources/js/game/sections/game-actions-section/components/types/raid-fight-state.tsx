import { BattleMessage } from "../../../../lib/game/actions/battle/types/battle-message-type";

export default interface RaidFightState {
    is_attacking: boolean;
    battle_messages: BattleMessage[]|[];
    character_current_health: number;
    monster_current_health: number;
    attacks_left: number;
    error_message: string;
}