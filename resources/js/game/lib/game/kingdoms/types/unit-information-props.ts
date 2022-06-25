import UnitDetails from "../unit-details";
import InformationPropsBase from "../information-props-base";

export default interface UnitInformationProps extends InformationPropsBase{

    unit: UnitDetails;

    close: (unit?: UnitDetails) => void;

    kingdom_id: number;
}
