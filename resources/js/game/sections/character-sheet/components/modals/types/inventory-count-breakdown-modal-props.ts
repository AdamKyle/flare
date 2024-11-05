import InventoryType from "../../../../../lib/game/character/inventory-type";

export default interface InventoryCountBreakdownModalProps {
    inventory_breakdown: InventoryType;
    is_open: boolean;
    manage_modal: () => void;
    title: string;
}
