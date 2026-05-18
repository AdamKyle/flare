import { FameTasks } from "../deffinitions/faction-loaylaty";

export default interface FactionLoyaltyProps {
    user_id: number;

    character_id: number;

    update_faction_action_tasks: (fameTasks: FameTasks[] | null) => void;

    can_craft: boolean;

    can_attack: boolean;

    character_map_id: number | null;

    is_automation_running: boolean;

    is_faction_loyalty_automation_running: boolean;

    is_delve_running: boolean;
}
