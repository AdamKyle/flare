import KingdomDetails from "../../deffinitions/kingdom-details";

export default interface BuildingsToUpgradeSectionProps {
    kingdom: KingdomDetails;
    user_id: number;
    repair: boolean;
}
