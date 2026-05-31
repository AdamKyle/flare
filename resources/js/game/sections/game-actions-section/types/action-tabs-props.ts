import React from "react";
import {
    FactionLoyaltyWarningNotice,
    FameTasks,
} from "../../../components/faction-loyalty/deffinitions/faction-loaylaty";

export default interface ActionTabsProps {
    children: React.ReactNode;

    character_id: number;

    character_map_id: number | null;

    user_id: number;

    use_tabs: boolean;

    update_faction_action_tasks: (fameTasks: FameTasks[] | null) => void;

    can_attack: boolean;

    can_craft: boolean;

    is_automation_running: boolean;

    is_faction_loyalty_automation_running: boolean;

    is_delve_running: boolean;

    has_faction_loyalty_warning: boolean;

    faction_loyalty_warning_notices: FactionLoyaltyWarningNotice[];

    update_faction_loyalty_warning: (
        hasWarning: boolean,
        warningNotices?: FactionLoyaltyWarningNotice[],
    ) => void;
}

export interface ActionTab {
    key: string;
    name: string;
    has_warning?: boolean;
}
