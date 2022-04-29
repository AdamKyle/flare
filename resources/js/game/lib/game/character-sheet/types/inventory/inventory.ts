import InventoryDetails from "./inventory-details";
import SetDetails from "./set-details";
import UsableItemsDetails from "./usable-items-details";

export default interface Inventory {

    equipped: InventoryDetails[] | [];

    inventory: InventoryDetails[] | [];

    quest_items: InventoryDetails[] | [];

    usable_items: UsableItemsDetails[] | [];

    savable_sets: SetDetails[] | [];

    usable_sets: SetDetails[] | [];

    sets: {[key: string]: {equippable: boolean; items: InventoryDetails[] | [], set_id: number}}

    set_is_equipped: boolean;

    set_name_equipped: string;
}
