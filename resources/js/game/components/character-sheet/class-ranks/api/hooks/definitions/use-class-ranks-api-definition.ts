import ClassRankDefinition from '../../definitions/class-rank-definition';

export default interface UseClassRanksApiDefinition {
  data: ClassRankDefinition[];
  loading: boolean;
  error: string | null;
  switchingClassId: number | null;
  successMessage: string | null;
  mutationError: string | null;
  switchClass: (gameClassId: number) => Promise<void>;
}
