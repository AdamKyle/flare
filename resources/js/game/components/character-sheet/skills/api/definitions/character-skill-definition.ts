export default interface CharacterSkillDefinition {
  id: number;
  character_id: number;
  name: string;
  skill_type: string;
  xp: number;
  xp_max: number;
  level: number;
  max_level: number;
  can_train: boolean;
  is_training: boolean;
  is_locked: boolean;
  is_class_skill: boolean;
}
