export default interface ManageTreasuryModalProps {

    is_open: boolean;

    handle_close: () => void;

    character_gold: number;

    treasury: number;

    morale: number;

    kingdom_id: number;

    character_id: number;
}
