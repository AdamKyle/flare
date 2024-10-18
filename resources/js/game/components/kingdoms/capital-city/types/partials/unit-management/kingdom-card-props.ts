import UnitManagementKingdoms from "../../../deffinitions/unit-management-kingdoms";
import UnitQueue from "../../../deffinitions/unit-queue";

export default interface KingdomCardProps {
    kingdom: UnitManagementKingdoms;
    unit_queue: UnitQueue[] | [];
    open_kingdom_ids: Set<Number>;
    get_bulk_input_value: (kingdomId: number) => number | string;
    get_kingdom_queue_summary: (kingdomId: number) => string | null;
    manage_card_state: (kingdomId: number) => void;
    handle_bulk_manage_card_stateamount_change: (
        e: React.ChangeEvent<HTMLInputElement>,
        kingdomId: number,
    ) => void;
    is_bulk_queue_disabled: () => boolean;
    fetch_units_to_show: () => any[] | [];
    get_unit_amount: (kingdomId: number, unitType: string) => number | string;
    handle_unit_amount_change: (
        kingdomId: number,
        unitType: string,
        amount: number | string,
        returnArray: boolean,
    ) => void;
}
