import InventoryDetails from "../inventory/inventory-details";

export default interface InventoryActionConfirmationModalProps {
    is_open: boolean;

    manage_modal: () => void;

    update_inventory: (inventory: {[key: string]: InventoryDetails[]}) => void;

    title: string;

    url: string;
}
