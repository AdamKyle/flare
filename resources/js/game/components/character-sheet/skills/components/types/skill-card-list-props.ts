import CharacterSkillDefinition from '../../api/definitions/character-skill-definition';

export default interface SkillCardListProps {
  skills: CharacterSkillDefinition[];
  empty_message: string;
  on_open_skill: (skill: CharacterSkillDefinition) => void;
}
