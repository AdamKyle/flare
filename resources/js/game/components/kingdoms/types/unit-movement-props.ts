import UnitMovementDetails from "../../../../sections/kingdoms/queues/deffinitions/unit-movement-details";

export default interface UnitMovementProps {

    units_in_movement: UnitMovementDetails[]|[]

    dark_tables: boolean;

    character_id: number;
}
