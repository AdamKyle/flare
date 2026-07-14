import { ItemDefinition } from "../deffinitions/item-definition";
import GemBagSlotDetails from "../../../../../../../lib/game/character-sheet/types/inventory/gem-bag-slot-details";

export default interface ItemDetailsProps {
    item: ItemDefinition;
    character_id: number;
    preloaded_attached_gems?: GemBagSlotDetails[];
}
