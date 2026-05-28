export default interface CharacterSkillTabsProps {
    character_id: number;

    user_id: number;

    is_dead: boolean;

    is_automation_running: boolean;

    is_faction_loyalty_automation_running: boolean;

    is_delve_running: boolean;

    active_automation: {
        type: number;
        name: string;
        timer_seconds: number;
    } | null;

    finished_loading: boolean;
}
