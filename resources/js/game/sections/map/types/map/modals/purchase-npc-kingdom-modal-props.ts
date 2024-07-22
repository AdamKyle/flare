export default interface PurchaseNpcKingdomModalProps {
    is_open: boolean;

    handle_close: (closeParentModel: boolean) => void;

    character_id: number;

    map_id: number;

    kingdom_id: number;

    kingdom_name: string;
}
