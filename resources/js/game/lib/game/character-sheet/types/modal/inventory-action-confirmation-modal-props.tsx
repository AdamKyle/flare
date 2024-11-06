import InventoryDetails from "../inventory/inventory-details";
import React from "react";

export default interface InventoryActionConfirmationModalProps {
    is_open: boolean;

    manage_modal: () => void;

    update_inventory: (inventory: {
        [key: string]: InventoryDetails[];
    }) => void;

    set_success_message: (message: string) => void;

    reset_selected_items?: () => void;

    title: string;

    url: string;

    children?: React.ReactNode;

    ajax_params?: any;

    is_large_modal: boolean;
}
