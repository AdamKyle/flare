import InventoryDetails from "../../game/character-sheet/types/inventory/inventory-details";
import UsableItemsDetails from "../../game/character-sheet/types/inventory/usable-items-details";
import GemBagDetails from "../../game/character-sheet/types/inventory/gem-bag-details";

export default interface ItemNameColorationProps {

    item: InventoryDetails;

    on_click?: (item: InventoryDetails | UsableItemsDetails) => any
}
