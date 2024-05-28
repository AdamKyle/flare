import { FactionLoyaltyNpc } from "../deffinitions/faction-loaylaty";

export default interface FactionNpcSectionProps {
    faction_loyalty_npc: FactionLoyaltyNpc;
    can_craft: boolean;
    can_attack: boolean;
}
