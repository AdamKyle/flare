export default interface GemAbilityFormStateDefinition {
  name: string;
  description: string;
  ability_type: string | null;
  effect_type: string | null;
  attack_types: string[];
  proc_chance: string;
  effect_value: string;
  scaling_source: string | null;
  enabled: boolean;
}
