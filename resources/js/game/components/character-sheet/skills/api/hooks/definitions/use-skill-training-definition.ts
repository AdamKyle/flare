import SkillTrainingResponseDefinition from '../../definitions/skill-training-response-definition';

export default interface UseSkillTrainingDefinition {
  submitting: boolean;
  error: string | null;
  train: (
    skillId: number,
    xpPercentage: number
  ) => Promise<SkillTrainingResponseDefinition | null>;
  cancel: (skillId: number) => Promise<SkillTrainingResponseDefinition | null>;
}
