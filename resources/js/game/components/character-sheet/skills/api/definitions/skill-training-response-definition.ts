import CharacterSkillDefinition from './character-skill-definition';

export default interface SkillTrainingResponseDefinition {
  message: string;
  skills: {
    training_skills: CharacterSkillDefinition[];
  };
}
