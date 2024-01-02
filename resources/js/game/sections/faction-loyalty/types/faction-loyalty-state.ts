import {FactionLoyaltyNpc} from "../deffinitions/faction-loaylaty-npc";

export type FactionLoyaltyNpcListItem = {
    name: string;
    id: number;
}

export default interface FactionLoyaltyState {
    is_loading: boolean;
    selected_npc: FactionLoyaltyNpcListItem | null;
    error_message: string | null;
    npcs: FactionLoyaltyNpcListItem[]|[];
    game_map_name: string | null;
    faction_loyalty_npc: FactionLoyaltyNpc | null;
}
