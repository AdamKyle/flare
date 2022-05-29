export default interface DuelType {
    character_id: number;

    attacker_max_health: number;

    attacker_health: number;

    defender_max_health: number;

    defender_health: number;

    battle_messages: {message: string, type: string}[] | [];
}
