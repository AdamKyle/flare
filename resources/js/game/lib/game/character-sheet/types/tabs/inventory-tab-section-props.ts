import InventoryDetails from "../inventory/inventory-details";
import CoreInventoryTabProps from "./core-inventory-tab-props";
import SetDetails from "../inventory/set-details";
import UsableItemsDetails from "../inventory/usable-items-details";

export default interface InventoryTabSectionProps extends CoreInventoryTabProps {

    inventory: InventoryDetails[] | [];

    usable_items: UsableItemsDetails[] | [];

    usable_sets: SetDetails[] | [];
}
