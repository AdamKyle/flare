import { BattleMessage } from "../../../../lib/game/actions/battle/types/battle-message-type";

export default interface RaidFightState {
    is_attacking: boolean;
    battle_messages: BattleMessage[]|[];
}