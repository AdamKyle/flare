import KingdomDetails from "../deffinitions/kingdom-details";

export default interface KingdomDetailsProps {
    kingdom: KingdomDetails;

    character_gold: number;

    close_details: () => void;

    show_resource_transfer_card: () => void;

    show_small_council: () => void;

    reset_resource_transfer: boolean;
}
