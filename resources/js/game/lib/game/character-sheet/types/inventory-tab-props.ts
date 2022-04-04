import InventoryDetails from "./inventory/inventory-details";

export default interface InventoryTabProps {

    inventory: InventoryDetails[];

    character_id: number;

    dark_table: boolean;

    is_dead: boolean;
}
