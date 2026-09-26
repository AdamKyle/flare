import CharacterSkillDefinition from '../../definitions/character-skill-definition';

export default interface UseCharacterSkillsDefinition {
  training_skills: CharacterSkillDefinition[];
  crafting_skills: CharacterSkillDefinition[];
  loading: boolean;
  error: string | null;
  replace_training_skills: (trainingSkills: CharacterSkillDefinition[]) => void;
}
