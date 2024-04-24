import BuildingDetails from "../../../../sections/kingdoms/buildings/deffinitions/building-details";
import BuildingInQueueDetails from "../deffinitions/building-in-queue-details";

export default interface BuildingsTableProps {
    buildings: BuildingDetails[] | [];

    buildings_in_queue: BuildingInQueueDetails[] | [];

    dark_tables: boolean;

    view_building: (building?: BuildingDetails) => void;

    view_port: number;
}
