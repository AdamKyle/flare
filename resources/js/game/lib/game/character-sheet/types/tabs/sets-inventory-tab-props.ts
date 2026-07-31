import CoreInventoryTabProps from "./core-inventory-tab-props";
import SetDetails from "../inventory/set-details";
import InventoryDetails from "../inventory/inventory-details";

export default interface SetsInventoryTabProps extends CoreInventoryTabProps {
    sets: {
        [key: string]: {
            equippable: boolean;
            items: InventoryDetails[] | [];
            equipped: boolean;
            set_id: number;
            is_batch_crafting_set: boolean;
            max_slots: number | null;
            current_slots: number;
            remaining_slots: number | null;
            can_empty: boolean;
            empty_disabled_reason: string | null;
        };
    };

    savable_sets: SetDetails[] | [];

    set_name_equipped: string;

    is_automation_running: boolean;

    disable_tabs: () => void;

    manage_skills: (
        itemId: number,
        itemSkills: any[] | [],
        itemSkillProgressions: any[],
    ) => void;

    view_port: number;
}
