import InventoryDetails from "../inventory/inventory-details";

export default interface CoreInventoryTabProps {
    is_dead: boolean;

    update_inventory: (inventory: {
        [key: string]: InventoryDetails[];
    }) => void;

    dark_tables: boolean;

    character_id: number;
}
