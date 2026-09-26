import CharacterSkillDefinition from '../../api/definitions/character-skill-definition';

export default interface UseOpenCharacterSkillDetailDefinition {
  openCharacterSkillDetail: (
    characterId: number,
    skill: CharacterSkillDefinition,
    onTrainingSkillsChanged: (
      trainingSkills: CharacterSkillDefinition[]
    ) => void
  ) => void;
}
