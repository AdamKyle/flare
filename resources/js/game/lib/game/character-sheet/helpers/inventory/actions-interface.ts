import InventoryDetails from "../../types/inventory/inventory-details";
import UsableItemsDetails from "../../types/inventory/usable-items-details";

export default interface ActionsInterface {

    actions(row: InventoryDetails | UsableItemsDetails): JSX.Element;
}
