import KingdomLogDetails from "../kingdom-log-details";

export default interface KingdomLogProps {
    log: KingdomLogDetails;

    close_details: () => void;
}
