export default interface PassiveSkillTreeDefinition {
  id: number;
  name: string;
  effect_type: number;
  max_level: number;
  parent_id: number | null;
  unlocks_at_level: number | null;
}
