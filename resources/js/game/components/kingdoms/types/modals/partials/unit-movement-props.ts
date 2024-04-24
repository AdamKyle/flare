import KingdomReinforcementType from "../../kingdom-reinforcement-type";
import SelectedUnitsToCallType from "../../selected-units-to-call-type";

export default interface UnitMovementProps {
    kingdoms: KingdomReinforcementType[] | [];

    update_kingdoms_selected: (kingdomsSelected: number[] | []) => void;

    update_units_selected: (
        unitsSelected: SelectedUnitsToCallType[] | [],
    ) => void;
}
