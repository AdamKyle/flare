import SetDetails from "../inventory/set-details";

export default interface MoveModalProps {

    is_open: boolean;

    usable_sets: SetDetails[] | [];

    manage_modal: () => void;

    move_item: (setId: number) => void;
}
