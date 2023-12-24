export type FactionLoyaltyNpc = {
    name: string;
    id: number;
}

export default interface FactionLoyaltyState {

    is_loading: boolean;
    selected_npc: FactionLoyaltyNpc | null;
    error_message: string | null;
    npcs: FactionLoyaltyNpc[]|[];
    game_map_name: string | null;
}
