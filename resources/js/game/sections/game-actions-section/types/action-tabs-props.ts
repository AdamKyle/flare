import React from "react";
import { FameTasks } from "../../../components/faction-loyalty/deffinitions/faction-loaylaty";

export default interface ActionTabsProps {
    children: React.ReactNode;

    character_id: number;

    user_id: number;

    use_tabs: boolean;

    update_faction_action_tasks: (fameTasks: FameTasks[] | null) => void;

    can_attack: boolean;

    can_craft: boolean;
}
