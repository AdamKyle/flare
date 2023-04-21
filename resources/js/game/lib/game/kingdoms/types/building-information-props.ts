import BuildingDetails from "../building-details";
import InformationPropsBase from "../information-props-base";

export default interface BuildingInformationProps extends InformationPropsBase {
    building: BuildingDetails;

    close: (building?: BuildingDetails) => void;

    character_gold: number;

    kingdom_building_time_reduction: number;
}
