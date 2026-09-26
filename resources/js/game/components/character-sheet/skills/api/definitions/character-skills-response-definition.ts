import CharacterSkillDefinition from './character-skill-definition';

export default interface CharacterSkillsResponseDefinition {
  training_skills: CharacterSkillDefinition[];
  crafting_skills: CharacterSkillDefinition[];
}
