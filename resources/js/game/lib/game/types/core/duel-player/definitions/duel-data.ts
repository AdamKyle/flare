import {BattleMessage} from "../../../../actions/battle/types/battle-message-type";

export default interface DuelData {
    attacker_id: number;
    health_object: HealthObject;
    defender_id: number;
    messages: DuelMessages[]|[];
    defender_atonement: string;
    attacker_atonement: string;
}

interface HealthObject {
    attacker_max_health: number;
    attacker_health: number;
    defender_max_health: number;
    defender_health: number;
}

export interface DuelMessages extends BattleMessage {};
