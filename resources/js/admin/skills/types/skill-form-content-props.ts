import SkillFormDefinition from '../api/definitions/skill-form-definition';

export default interface SkillFormContentProps {
  skill_id: number | null;
  on_saved: (skill: SkillFormDefinition) => void;
  on_cancel: () => void;
}
