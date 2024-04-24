import KingdomLogDetails from "../deffinitions/kingdom-log-details";

export default interface KingdomLogProps {
    log: KingdomLogDetails;

    close_details: () => void;
}
