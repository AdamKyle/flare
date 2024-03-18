import {UsableSets} from "../../../../../components/modals/chat-item-comparison/types/chat-item-comparison-state";

export default interface MoveModalProps {

    is_open: boolean;

    usable_sets: UsableSets[] | [];

    manage_modal: () => void;

    move_item: (setId: number) => void;
}
