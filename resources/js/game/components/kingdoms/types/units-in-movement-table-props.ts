import UnitMovementDetails from "../queues/deffinitions/unit-movement-details";

export default interface UnitsInMovementTableProps {
    units_in_movement: UnitMovementDetails[] | [];

    dark_tables: boolean;

    character_id: number;
}
