export default interface DuelType {
    character_id: number;

    attacker_max_health: number;

    attacker_health: number;

    defender_max_health: number;

    defender_health: number;

    battle_messages: BattleMessages[] | [];

    defender_atonement: string;

    attacker_atonement: string;
}

interface BattleMessages {
    message: string;
    type: string;
}
