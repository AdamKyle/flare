import SkillDetailDefinition from '../../definitions/skill-detail-definition';

export default interface UseSkillDetailDefinition {
  skill: SkillDetailDefinition | null;
  loading: boolean;
  error: string | null;
  refetch: () => void;
}
