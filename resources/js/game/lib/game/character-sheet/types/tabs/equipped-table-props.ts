import InventoryDetails from "../inventory/inventory-details";
import SetDetails from "../inventory/set-details";

export default interface EquippedTableProps {

    dark_table: boolean;

    equipped_items: InventoryDetails[] | [];

    is_dead: boolean;

    sets: {[key: string]: InventoryDetails[] | []};

    update_inventory: (inventory: {[key: string]: InventoryDetails[]}) => void;

    character_id: number;

    is_set_equipped: boolean;
}
