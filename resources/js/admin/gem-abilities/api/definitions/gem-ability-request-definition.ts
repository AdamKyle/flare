export default interface GemAbilityRequestDefinition {
  name: string;
  description: string;
  ability_type: string;
  effect_type: string;
  attack_types: string[];
  proc_chance: number | null;
  effect_value: number | null;
  scaling_source: string | null;
  enabled: boolean;
}
