import InformationPropsBase from "../../../../lib/game/kingdoms/deffinitions/information-props-base";
import BuildingDetails from "../deffinitions/building-details";

export default interface BuildingInformationProps extends InformationPropsBase {
    user_id: number;

    building: BuildingDetails;

    close: (building?: BuildingDetails) => void;

    character_gold: number;

    kingdom_building_time_reduction: number;
}
