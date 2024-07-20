import BuildingInQueueDetails from "../deffinitions/building-in-queue-details";
import BuildingDetails from "../buildings/deffinitions/building-details";

export default interface BuildingsTableProps {
    buildings: BuildingDetails[] | [];

    buildings_in_queue: BuildingInQueueDetails[] | [];

    dark_tables: boolean;

    view_building: (building?: BuildingDetails) => void;

    view_port: number;
}
