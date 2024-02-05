import BuildingDetails from "../deffinitions/building-details";

export default interface ResourceBuildingExpansionProps {

    character_id: number;

    building: BuildingDetails;

    building_needs_to_be_repaired: boolean;
}
