import InventoryDetails from "../inventory/inventory-details";
import CoreInventoryTabProps from "./core-inventory-tab-props";
import SetDetails from "../inventory/set-details";

export default interface InventoryTabSectionProps extends CoreInventoryTabProps {

    inventory: InventoryDetails[] | [];

    usable_items: InventoryDetails[] | [];

    usable_sets: SetDetails[] | [];
}
