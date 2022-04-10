export default interface MonsterType {
    ac: number;

    accuracy: number;

    affix_resistance: number;

    agi: number;

    artifact_annulment: number;

    artifact_damage: number;

    attack_range: string;

    base_stat: number;

    casting_accuracy: number;

    chr: number;

    criticality: number;

    damage_stat: string;

    devouring_darkness_chance: number;

    devouring_light_chance: number;

    dex: number;

    dodge: number;

    dur: number;

    entrancing_chance: number;

    focus: number;

    has_artifacts: boolean;

    has_damage_spells: boolean;

    health_range: string;

    id: number;

    increases_damage_by: number|null;

    int: number;

    map_name: string;

    max_affix_damage: number;

    max_healing: number;

    max_level: number;

    name: string;

    spell_damage: number;

    spell_evasion: number;

    str: number;

    to_hit_base: number;
}
