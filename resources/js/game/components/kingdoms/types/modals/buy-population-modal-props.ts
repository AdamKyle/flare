import KingdomDetails from "../../deffinitions/kingdom-details";

export default interface BuyPopulationModalProps {
    kingdom: KingdomDetails;

    is_open: boolean;

    handle_close: () => void;

    gold: number;
}
