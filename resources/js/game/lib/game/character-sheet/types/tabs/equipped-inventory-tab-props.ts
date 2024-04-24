import InventoryDetails from "../inventory/inventory-details";
import CoreInventoryTabProps from "./core-inventory-tab-props";

export default interface EquippedInventoryTabProps
    extends CoreInventoryTabProps {
    equipped_items: InventoryDetails[] | [];

    is_set_equipped: boolean;

    sets: {
        [key: string]: {
            equippable: boolean;
            items: InventoryDetails[] | [];
            set_id: number;
        };
    };

    is_automation_running: boolean;

    disable_tabs: () => void;

    manage_skills: (
        itemId: number,
        itemSkills: any[] | [],
        itemSkillProgressions: any[],
    ) => void;

    view_port: number;
}
