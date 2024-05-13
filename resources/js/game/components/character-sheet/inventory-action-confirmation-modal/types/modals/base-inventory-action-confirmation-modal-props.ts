import { InventoryActionConfirmationType } from "../../helpers/enums/inventory-action-confirmation-type";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";

export default interface BaseInventoryActionConfirmationModalProps {
    type: InventoryActionConfirmationType;

    is_open: boolean;

    manage_modal: () => void;

    title: string;

    update_inventory: (inventory: {
        [key: string]: InventoryDetails[];
    }) => void;

    set_success_message: (message: string) => void;

    data: {
        url: string;
        params?: {};
    };

    selected_item_names?: string[] | [];
}
