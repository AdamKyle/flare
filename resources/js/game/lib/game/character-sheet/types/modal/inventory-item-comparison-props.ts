import InventoryDetails from "../inventory/inventory-details";

export default interface InventoryItemComparisonProps {
    is_open: boolean;

    manage_modal: () => void;

    title: string;

    slot_id: number;

    item_type: string;

    character_id: number;

    update_inventory: (inventory: {[key: string]: InventoryDetails[]}) => void
}
