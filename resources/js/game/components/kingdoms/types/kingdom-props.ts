import KingdomDetails from "../deffinitions/kingdom-details";

export default interface KingdomProps {
    close_details: () => void;

    kingdom: KingdomDetails;

    kingdoms: KingdomDetails[] | [];

    dark_tables: boolean;

    character_gold: number;

    view_port: number;

    user_id: number;

    has_capital_city: boolean;
}
