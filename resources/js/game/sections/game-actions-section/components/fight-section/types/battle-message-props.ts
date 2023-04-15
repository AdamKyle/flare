import {BattleMessage} from "../../../../../lib/game/actions/battle/types/battle-message-type";

export interface BattleMessageProps {
    battle_messages: BattleMessage[]|[];
    is_small: boolean;
}
