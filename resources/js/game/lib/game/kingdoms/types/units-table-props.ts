import UnitDetails from "../unit-details";
import UnitsInQueue from "../units-in-queue";
import CurrentUnitDetails from "../current-unit-details";
import BuildingDetails from "../building-details";

export default interface UnitsTableProps {

    units: UnitDetails[] | [];

    buildings: BuildingDetails[] | [];

    dark_tables: boolean;

    view_unit: (unit?: UnitDetails) => void;

    units_in_queue: UnitsInQueue[]|[]

    current_units: CurrentUnitDetails[]|[]
}
