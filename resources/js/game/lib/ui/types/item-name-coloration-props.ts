import InventoryDetails from "../../game/character-sheet/types/inventory/inventory-details";

export default interface ItemNameColorationProps {

    item: InventoryDetails;

    on_click?: (item: InventoryDetails) => any
}
