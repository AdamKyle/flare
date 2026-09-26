import CharacterSkillDefinition from '../../api/definitions/character-skill-definition';

export default interface SkillCardProps {
  skill: CharacterSkillDefinition;
  on_open: (skill: CharacterSkillDefinition) => void;
}
