export default interface ServerFightProps {
    monster_health: number;
    character_health: number;
    monster_max_health: number;
    character_max_health: number;
    monster_name: string;
    preforming_action: boolean;
    character_name: string;
    is_dead: boolean;
    can_attack: boolean;
    monster_id: number;
    attack: (type: AttackTypes) => void;
    children: React.ReactNode;
    manage_server_fight?: () => void;
    revive: () => void;
}

type AttackTypes =
    | "attack"
    | "cast"
    | "cast_and_attack"
    | "attack_and_cast"
    | "defend";
