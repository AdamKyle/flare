import ActiveBoonDefinition from '../../definitions/active-boon-definition';

export default interface UseActiveBoonsApiDefinition {
  boons: ActiveBoonDefinition[];
  loading: boolean;
  error: string | null;
  successMessage: string | null;
  mutationError: string | null;
  fillingBoonId: number | null;
  removingBoonId: number | null;
  refresh: () => void;
  replaceBoons: (boons: ActiveBoonDefinition[]) => void;
  fillUpBoon: (boonId: number) => Promise<void>;
  removeBoon: (boonId: number) => Promise<void>;
}
