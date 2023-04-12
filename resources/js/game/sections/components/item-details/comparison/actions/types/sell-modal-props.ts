import InventoryComparisonAdjustment from "../../types/inventory-comparison-adjustment";

export default interface SellModalProps {

    is_open: boolean;

    manage_modal: () => void;

    sell_item: () => void;

    item: InventoryComparisonAdjustment;
}
