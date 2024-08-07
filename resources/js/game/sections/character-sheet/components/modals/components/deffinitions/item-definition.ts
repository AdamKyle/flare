import ItemAtonementDefinition from "./item-atonement-definition";
import { AppliedStack } from "./holy-definition";
import AffixDefinition from "./affix-definition";

export interface ItemDefinition {
    id: number;
    description: string;
    item_atonements: ItemAtonementDefinition;
    str_modifier: number;
    dex_modifier: number;
    agi_modifier: number;
    chr_modifier: number;
    dur_modifier: number;
    int_modifier: number;
    focus_modifier: number;
    base_damage: number;
    base_ac: number;
    base_healing: number;
    base_damage_mod: number;
    base_ac_mod: number;
    base_healing_mod: number;
    skill_name: string | null;
    skill_bonus: number;
    skill_training_bonus: number;
    spell_evasion: number;
    healing_reduction: number;
    affix_damage_reduction: number;
    devouring_light: number;
    devouring_darkness: number;
    ambush_chance: number;
    ambush_resistance: number;
    counter_chance: number;
    counter_resistance: number;
    affix_count: number;
    item_prefix: AffixDefinition | null;
    item_suffix: AffixDefinition | null;
    socket_amount: number;
    holy_stacks: number;
    holy_stack_count: number;
    holy_stacks_applied: number;
    holy_stack_stat_bonus: number;
    resurrection_chance: number;
    applied_stacks: AppliedStack[];
    affix: AffixDefinition;
    gem_slots: number;
}
