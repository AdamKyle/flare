import { BattleMessage } from "../../../../lib/game/actions/battle/types/battle-message-type";

export default interface RaidFightState {
    is_attacking: boolean;
    battle_messages: BattleMessage[]|[];
    character_current_health: number;
    monster_current_health: number;
}