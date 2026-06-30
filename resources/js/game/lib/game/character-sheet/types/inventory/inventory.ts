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

    sets: {
        [key: string]: {
            equippable: boolean;
            items: InventoryDetails[] | [];
            set_id: number;
            equipped: boolean;
            is_batch_crafting_set: boolean;
            max_slots: number | null;
            current_slots: number;
            remaining_slots: number | null;
            can_empty: boolean;
            empty_disabled_reason: string | null;
        };
    };

    set_is_equipped: boolean;

    set_name_equipped: string;
}
