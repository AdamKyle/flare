export default interface ConjureModalProps {
    is_open: boolean;

    handle_close: () => void;

    title: string;

    character_id: number;
}
