export default interface PassiveSkillChildDefinition {
  id: number;
  name: string;
  effect_type: number;
  max_level: number;
  unlocks_at_level: number | null;
}
