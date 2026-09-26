import CharacterSkillDefinition from './character-skill-definition';

export default interface CharacterSkillsUpdateEventDefinition {
  trainingSkills: CharacterSkillDefinition[];
  craftingSkills: CharacterSkillDefinition[];
}
