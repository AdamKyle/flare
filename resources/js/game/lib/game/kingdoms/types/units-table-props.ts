import UnitDetails from "../unit-details";
import UnitsInQueue from "../units-in-queue";

export default interface UnitsTableProps {

    units: UnitDetails[] | [];

    dark_tables: boolean;

    view_unit: (unit?: UnitDetails) => void;

    units_in_queue: UnitsInQueue[]|[]
}
