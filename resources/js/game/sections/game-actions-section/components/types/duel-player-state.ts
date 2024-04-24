import { DuelMessages } from "../../../../lib/game/types/core/duel-player/definitions/duel-data";

export default interface DuelPlayerState {
    character_id: number;
    defender_id: number;
    show_attack_section: boolean;
    preforming_action: boolean;
    attacker_max_health: number;
    attacker_health: number;
    defender_max_health: number;
    defender_health: number;
    battle_messages: DuelMessages[] | [];
    error_message: string | null;
    defender_atonement: string;
    attacker_atonement: string;
}
