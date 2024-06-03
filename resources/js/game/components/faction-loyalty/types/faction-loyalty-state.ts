import {
    FactionLoyalty,
    FactionLoyaltyNpc,
} from "../deffinitions/faction-loaylaty";

export type FactionLoyaltyNpcListItem = {
    name: string;
    id: number;
};

export default interface FactionLoyaltyState {
    is_loading: boolean;
    is_processing: boolean;
    selected_npc: FactionLoyaltyNpcListItem | null;
    error_message: string | null;
    success_message: string | null;
    npcs: FactionLoyaltyNpcListItem[] | [];
    game_map_name: string | null;
    faction_loyalty: FactionLoyalty | null;
    selected_faction_loyalty_npc: FactionLoyaltyNpc | null;
    attack_type: string | null;
}
