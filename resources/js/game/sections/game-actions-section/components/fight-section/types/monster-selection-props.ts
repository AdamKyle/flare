export default interface MonsterSelectionProps {
    set_monster_to_fight: (data: any) => void;
    monsters: { label: string; value: number }[] | [];
    default_monster: { label: string; value: number }[] | [];
    attack: () => void;
    is_attack_disabled: boolean;
    close_monster_section?: () => void;
}
