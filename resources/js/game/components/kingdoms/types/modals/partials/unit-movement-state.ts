import SelectedUnitsToCallType from "../../selected-units-to-call-type";

export default interface UnitMovementState {
    selected_kingdoms: number[];

    selected_units: SelectedUnitsToCallType[] | [];
}
