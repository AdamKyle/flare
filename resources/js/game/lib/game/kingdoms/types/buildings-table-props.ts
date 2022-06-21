import BuildingDetails from "../building-details";

export default interface BuildingsTableProps {

    buildings: BuildingDetails[] | [];

    dark_tables: boolean;

    view_building: (building?: BuildingDetails) => void;
}
