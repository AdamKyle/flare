import UnitMovementDetails from "../deffinitions/unit-movement-details";

export interface BaseQueue {
    name: string;
    id: number;
}

export interface BuildingQueue extends BaseQueue {
    from_level: number | null;
    to_level: number | null;
    type: string;
    time_remaining: number;
}

export interface UnitQueue extends BaseQueue {
    recruit_amount: number;
    time_remaining: number;
}

export interface BuildingExpansionQueue extends BaseQueue {
    from_slot: number;
    to_slot: number;
    time_remaining: number;
}

export interface UnitMovementQueues extends UnitMovementDetails, BaseQueue {}

export interface queues {
    building_queues: BuildingQueue[] | [];
    unit_recruitment_queues: UnitQueue[] | [];
    unit_movement_queues: UnitMovementQueues[] | [];
    building_expansion_queues: BuildingExpansionQueue[] | [];

    [key: string]: BaseQueue[] | [];
}

export default interface KingdomQueueState {
    loading: boolean;
    error_message: string | null;
    success_message: string | null;
    queues: queues | null;
}
