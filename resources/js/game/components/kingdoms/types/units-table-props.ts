import BuildingDetails from "../../../../sections/kingdoms/buildings/deffinitions/building-details";
import CurrentUnitDetails from "../deffinitions/current-unit-details";
import UnitDetails from "../deffinitions/unit-details";
import UnitsInQueue from "../deffinitions/units-in-queue";

export default interface UnitsTableProps {
    units: UnitDetails[] | [];

    buildings: BuildingDetails[] | [];

    dark_tables: boolean;

    view_unit: (unit?: UnitDetails) => void;

    units_in_queue: UnitsInQueue[] | [];

    current_units: CurrentUnitDetails[] | [];
}
