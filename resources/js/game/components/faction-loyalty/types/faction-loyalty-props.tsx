import { FameTasks } from "../deffinitions/faction-loaylaty";

export default interface FactionLoyaltyProps {
    user_id: number;

    character_id: number;

    update_faction_action_tasks: (fameTasks: FameTasks[] | null) => void;

    can_craft: boolean;

    can_attack: boolean;

    character_map_id: number | null;
}
