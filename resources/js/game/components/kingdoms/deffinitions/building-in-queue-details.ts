export default interface BuildingInQueueDetails {
    building_id: number;

    character_id: number;

    completed_at: string;

    created_at: string;

    id: number;

    is_capital_city_managed?: boolean;

    kingdom_id: number;

    paid_amount: number;

    paid_with_gold: boolean;

    phase_timer_label?: string;

    started_at: string;

    to_level: number;

    updated_at: string;
}
