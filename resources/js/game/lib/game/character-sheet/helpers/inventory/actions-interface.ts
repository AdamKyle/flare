import InventoryDetails from "../../types/inventory/inventory-details";
import UsableItemsDetails from "../../types/inventory/usable-items-details";
import GemBagDetails from "../../types/inventory/gem-bag-details";
import {ReactNode} from "react";

export default interface ActionsInterface {

    actions(row: InventoryDetails | UsableItemsDetails): ReactNode;
}
