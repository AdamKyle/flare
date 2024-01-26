import BuildingDetails from "../deffinitions/building-details";

export default interface ResourceBuildingExpansionProps {
    building: BuildingDetails;

    building_needs_to_be_repaired: boolean;
}
