import KingdomDetails from "../kingdom-details";

export default interface SmallBuildingsSectionsProps {
    kingdom: KingdomDetails;

    dark_tables: boolean;

    close_selected: () => void;
}
