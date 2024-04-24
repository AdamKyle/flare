import InventoryDetails from "../../../lib/game/character-sheet/types/inventory/inventory-details";
import ItemDefinition from "../deffinitions/item-definition";

export default interface ItemProps {
    item: InventoryDetails | ItemDefinition;
}
