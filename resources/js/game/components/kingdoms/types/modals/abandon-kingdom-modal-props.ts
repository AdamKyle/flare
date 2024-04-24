export default interface AbandonKingdomModalProps {
    is_open: boolean;

    handle_close: () => void;

    handle_kingdom_close: () => void;

    kingdom_id: number;
}
