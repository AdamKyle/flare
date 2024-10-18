import Kingdom from "../../../deffinitions/kingdom";
import { BuildingQueue } from "../../../../deffinitions/building-queue";
import Building from "../../../deffinitions/building";

export default interface OpenKingdomCardForBuildingManagementProps {
    kingdom: Kingdom;
    building_queue: BuildingQueue[];
    toggle_queue_all_buildings: (kingdomId: number) => void;
    has_building_in_queue: (kingdom: Kingdom, building: Building) => boolean;
    toggle_building_queue: (kingdomId: number, buildingId: number) => void;
}
