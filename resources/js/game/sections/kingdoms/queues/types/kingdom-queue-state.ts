
export interface BuildingQueue {
    name: string,
    id: number,
    from_level?: number;
    to_level?: number;
    type: string;
    time_remaining: number;
}

export interface UnitQueue {
    name: string,
    id: number,
    recruit_amount: number;
    time_remaining: number;
}

interface KingdomMovementDetails {
    name: string,
    x: number,
    y: number,
    plane: string
}

export interface UnitMovementQueue {
    name: string,
    id: number,
    moving_from_kingdom: KingdomMovementDetails,
    moving_to_kingdom: KingdomMovementDetails,
    reason: string,
    time_remaining: number;
}

export interface BuildingExpansionQueue {
    name: string,
    id: number,
    from_slot: number;
    to_slot: number;
    time_remaining: number;
}

export interface queues {
    building_queues: BuildingQueue[]|[],
    unit_recruitment_queues: UnitQueue[]|[],
    unit_movement_queues: UnitMovementQueue[]|[],
    building_expansion_queues: BuildingExpansionQueue[]|[],
}

export default interface KingdomQueueState {
    loading: true,
    error_message: null,
    queues: queues | null,
}
