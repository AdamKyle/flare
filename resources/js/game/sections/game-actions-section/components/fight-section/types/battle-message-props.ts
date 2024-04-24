import { BattleMessage } from "../../types/battle-message-type";

export interface BattleMessageProps {
    battle_messages: BattleMessage[] | [];
    is_small: boolean;
}
