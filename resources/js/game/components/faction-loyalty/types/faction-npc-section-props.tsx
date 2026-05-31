import {
    FactionLoyaltyNpc,
    FactionLoyaltyWarningNotice,
} from "../deffinitions/faction-loaylaty";

export default interface FactionNpcSectionProps {
    character_id: number;
    faction_loyalty_npc: FactionLoyaltyNpc;
    can_craft: boolean;
    can_attack: boolean;
    character_map_id: number | null;
    attack_type: string | null;
    set_attack_type?: (attackType: string) => void;
    automation_disabled_reason?: string | null;
    is_automation_running?: boolean;
    is_faction_loyalty_automation_running?: boolean;
    is_delve_running?: boolean;
    automation_time_out?: number;
    is_automation_processing?: boolean;
    warning_notices?: FactionLoyaltyWarningNotice[];
    show_automation_screen?: () => void;
    stop_automation?: () => void;
    update_automation_timer?: (timeLeft: number) => void;
    update_warning_notices?: (
        hasWarning: boolean,
        warningNotices: FactionLoyaltyWarningNotice[],
    ) => void;
}
