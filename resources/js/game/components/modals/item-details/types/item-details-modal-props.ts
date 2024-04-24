import InventoryDetails from "../../../../lib/game/character-sheet/types/inventory/inventory-details";

export interface ItemDetailsModalProps {
    is_open: boolean;
    character_id: number;
    slot_id: number;
    is_automation_running: boolean;
    is_dead: boolean;

    manage_modal: () => void;
    update_inventory?: (inventory: {
        [key: string]: InventoryDetails[];
    }) => void;
    set_success_message?: (message: string) => void;
}
