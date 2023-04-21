export default interface ChangeNameModalProps {

    name: string;

    kingdom_id: number;

    is_open: boolean;

    handle_close: () => void;
}
