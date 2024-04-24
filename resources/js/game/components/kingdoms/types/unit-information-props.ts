import BuildingDetails from "../../../../sections/kingdoms/buildings/deffinitions/building-details";
import InformationPropsBase from "../deffinitions/information-props-base";
import UnitDetails from "../deffinitions/unit-details";

export default interface UnitInformationProps extends InformationPropsBase {
    unit: UnitDetails;

    buildings: BuildingDetails[] | [];

    close: (unit?: UnitDetails) => void;

    kingdom_id: number;

    character_gold: number;
}
