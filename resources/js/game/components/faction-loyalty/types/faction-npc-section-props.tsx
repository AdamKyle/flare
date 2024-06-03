import { FactionLoyaltyNpc } from "../deffinitions/faction-loaylaty";

export default interface FactionNpcSectionProps {
    character_id: number;
    faction_loyalty_npc: FactionLoyaltyNpc;
    can_craft: boolean;
    can_attack: boolean;
    character_map_id: number | null;
    attack_type: string | null;
}
