import KingdomDetails from "../../deffinitions/kingdom-details";

export default interface GoldBarManagementProps {
    character_id: number;
    kingdom: KingdomDetails;
    manage_gold_bar_management: () => void;
}
