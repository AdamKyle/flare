import { FameTasks } from "../deffinitions/faction-loaylaty";

export default interface FactionLoyaltyProps {
    character_id: number;

    update_faction_action_tasks: (fameTasks: FameTasks[] | null) => void;

    can_craft: boolean;

    can_attack: boolean;
}
