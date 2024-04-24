import InventoryDetails from "../inventory/inventory-details";
import SetDetails from "../inventory/set-details";

export default interface SetsTableProps {
    dark_table: boolean;

    sets: { [key: string]: InventoryDetails[] | [] };

    savable_sets: SetDetails[] | [];

    is_dead: boolean;

    character_id: number;

    update_inventory: (inventory: {
        [key: string]: InventoryDetails[];
    }) => void;

    set_name_equipped: string;
}
