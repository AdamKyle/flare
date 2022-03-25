import InventoryDetails from "../../types/inventory/inventory-details";

export default interface ActionsInterface {

    actions(row: InventoryDetails): JSX.Element;
}
