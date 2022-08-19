import KingdomDetails from "../kingdom-details";

export default interface KingdomDetailsProps {

    kingdom: KingdomDetails;

    character_gold: number;

    close_details: () => void;
}
