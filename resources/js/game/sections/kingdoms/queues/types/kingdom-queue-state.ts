import UnitMovementDetails from "../deffinitions/unit-movement-details";

export interface BuildingQueue {
    name: string,
    id: number,
    from_level: number | null;
    to_level: number | null;
    type: string;
    time_remaining: number;
}

export interface UnitQueue {
    name: string,
    id: number,
    recruit_amount: number;
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
    unit_movement_queues: UnitMovementDetails[]|[],
    building_expansion_queues: BuildingExpansionQueue[]|[],
}

export default interface KingdomQueueState {
    loading: boolean,
    error_message: null,
    queues: queues | null,
}
