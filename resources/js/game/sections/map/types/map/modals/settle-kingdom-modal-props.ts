export default interface SettleKingdomModalProps {
    is_open: boolean;

    handle_close: () => void;

    character_id: number;

    map_id: number;

    can_settle: boolean;
}
