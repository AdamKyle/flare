import InventoryDetails from "./inventory-details";
import SetDetails from "./set-details";

export default interface Inventory {

    equipped: InventoryDetails[] | [];

    inventory: InventoryDetails[] | [];

    quest_items: InventoryDetails[] | [];

    usable_items: InventoryDetails[] | [];

    savable_sets: SetDetails[] | [];

    usable_sets: SetDetails[] | [];

    sets: {[key: string]: InventoryDetails[] | []}

    set_is_equipped: boolean;

    set_name_equipped: string;
}
