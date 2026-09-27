import PassiveSkillFormDefinition from '../api/definitions/passive-skill-form-definition';

export default interface PassiveSkillFormContentProps {
  passive_skill_id: number | null;
  on_saved: (passive_skill: PassiveSkillFormDefinition) => void;
  on_cancel: () => void;
}
