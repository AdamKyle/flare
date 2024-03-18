
import GemDetails from "../../../../components/modals/chat-item-comparison/item-views/gem-details";
import GemBagSlotDetails from "../../../../lib/game/character-sheet/types/inventory/gem-bag-slot-details";

export type ActionTypes = 'attach-gem' | 'replace-gem'

export default interface AddingTheGemProps {
    gem_to_add: GemBagSlotDetails|null,
    do_action: (string: ActionTypes) => void
    action_disabled: boolean;
    socket_data: any,
}
