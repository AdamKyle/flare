export default interface UnitsInQueue {
    amount: number;
    character_id: number;
    completed_at: string;
    game_unit_id: number;
    gold_paid: number | null;
    id: number;
    is_capital_city_managed?: boolean;
    kingdom_id: number;
    started_at: string;
    capital_city_unit_queue_id?: number | null;
}
