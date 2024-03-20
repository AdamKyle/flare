import InventoryDetails from "../inventory/inventory-details";
import CoreInventoryTabProps from "./core-inventory-tab-props";
import SetDetails from "../inventory/set-details";
import UsableItemsDetails from "../inventory/usable-items-details";

export default interface InventoryTabSectionProps extends CoreInventoryTabProps {

    inventory: InventoryDetails[] | [];

    usable_items: UsableItemsDetails[] | [];

    usable_sets: SetDetails[] | [];

    is_automation_running: boolean;

    user_id: number;

    manage_skills: (itemId: number, itemSkills: any[], itemSkillProgressions: any[]) => void;

    view_port: number;
}
