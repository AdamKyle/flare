import InventoryDetails from "./inventory/inventory-details";
import SetDetails from "./inventory/set-details";
import { UsableSets } from "../../../../components/modals/item-details/types/item-details-modal-state";

export default interface InventoryTabProps {
    inventory: InventoryDetails[];

    usable_sets: UsableSets[] | [];

    character_id: number;

    dark_table: boolean;

    is_dead: boolean;

    update_inventory: (inventory: {
        [key: string]: InventoryDetails[];
    }) => void;

    set_success_message: (message: string) => void;

    is_automation_running: boolean;

    manage_skills: (
        itemId: number,
        itemSkills: any[] | [],
        itemSkillProgressions: any[],
    ) => void;

    view_port: number;

    manage_selected_items: (selectedItems: number[] | []) => void;
}
