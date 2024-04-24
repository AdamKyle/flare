export default interface ReviveProps {
    can_attack: boolean;
    is_character_dead: boolean;
    character_id: number;
    revive_call_back?: () => void;
}
