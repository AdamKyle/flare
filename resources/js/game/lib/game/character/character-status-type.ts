export default interface CharacterStatusType {
    can_attack: boolean;

    can_attack_again_at: number;

    can_craft: boolean;

    can_move: boolean;

    can_craft_again_at: number;

    is_dead: boolean;

    automation_locked: boolean;

    is_automation_running: boolean;

    is_faction_loyalty_automation_running: boolean;

    is_delve_running: boolean;

    active_automation: {
        type: number;
        name: string;
        timer_seconds: number;
    } | null;

    is_at_delve_location: boolean;

    can_set_delve_pack: boolean;

    is_silenced: boolean;
}
