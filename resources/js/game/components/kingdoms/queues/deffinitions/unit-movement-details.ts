export default interface UnitMovementDetails {
    id: number;

    kingdom_id: number;

    character_id: number;

    from_kingdom_name: string;

    to_kingdom_name: string;

    time_left: number;

    moving_to_x: number;

    moving_to_y: number;

    from_x: number;

    from_y: number;

    reason: string;

    is_moving: boolean;

    is_recalled: boolean;

    is_returning: boolean;

    is_attacking: boolean;

    resources_requested: boolean;
}
