export default interface CharacterStatusType {
    can_attack: boolean;

    can_attack_again_at: number;

    can_craft: boolean;

    can_craft_again_at: number;

    can_adventure: boolean;

    is_dead: boolean;

    automation_locked: boolean;

    is_silenced: boolean;
}
