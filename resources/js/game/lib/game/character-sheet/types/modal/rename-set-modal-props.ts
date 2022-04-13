export default interface RenameSetModalProps {
    is_open: boolean;

    manage_modal: () => void;

    rename_set: (name: string) => void;

    title: string;

    current_set_name: string;
}
