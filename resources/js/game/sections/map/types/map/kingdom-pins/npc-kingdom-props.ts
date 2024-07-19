import CharacterCurrenciesDetails from "../../../../../lib/game/types/character-currencies-details";

export interface NpcKingdomDefinition {
    id: number;
    x_position: number;
    y_position: number;
    npc_owned: boolean;
}

export default interface NpcKingdomProps {
    kingdoms: NpcKingdomDefinition[] | null;

    character_id: number;

    character_position: { x: number; y: number };

    currencies?: CharacterCurrenciesDetails;

    teleport_player: (data: {
        x: number;
        y: number;
        cost: number;
        timeout: number;
    }) => void;

    can_move: boolean;

    is_automation_running: boolean;

    is_dead: boolean;
}
