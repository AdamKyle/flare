import InventoryDetails from "../inventory/inventory-details";
import CoreInventoryTabProps from "./core-inventory-tab-props";

export default interface InventoryTabSectionProps extends CoreInventoryTabProps {

    inventory: InventoryDetails[] | [];

    usable_items: InventoryDetails[] | [];
}
