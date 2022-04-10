import CoreInventoryTabProps from "./core-inventory-tab-props";
import SetDetails from "../inventory/set-details";
import InventoryDetails from "../inventory/inventory-details";

export default interface SetsInventoryTabProps extends CoreInventoryTabProps {
    sets: {[key: string]: InventoryDetails[] | []};

    savable_sets: SetDetails[] | [];

    set_name_equipped: string;
}
