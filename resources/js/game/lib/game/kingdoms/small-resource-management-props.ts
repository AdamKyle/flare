import KingdomDetails from "./kingdom-details";

export default interface SmallResourceManagementProps {
    kingdom: KingdomDetails;

    dark_tables: boolean;

    close_selected: () => void;
}
