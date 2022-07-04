import UnitDetails from "../unit-details";
import InformationPropsBase from "../information-props-base";
import BuildingDetails from "../building-details";

export default interface UnitInformationProps extends InformationPropsBase{

    unit: UnitDetails;

    buildings: BuildingDetails[] | [];

    close: (unit?: UnitDetails) => void;

    kingdom_id: number;
}
