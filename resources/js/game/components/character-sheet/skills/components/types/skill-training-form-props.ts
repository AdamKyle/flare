import SkillDetailDefinition from '../../api/definitions/skill-detail-definition';

export default interface SkillTrainingFormProps {
  skill: SkillDetailDefinition;
  submitting: boolean;
  error: string | null;
  success_message: string | null;
  on_train: (xpPercentage: number) => void;
  on_cancel: () => void;
}
