import React from "react";
import {FameTasks} from "../../faction-loyalty/deffinitions/faction-loaylaty";

export default interface ActionTabsProps {

    children: React.ReactNode;

    character_id: number;

    use_tabs: boolean;

    update_faction_action_tasks: (fameTasks: FameTasks[] | null) => void;
}
