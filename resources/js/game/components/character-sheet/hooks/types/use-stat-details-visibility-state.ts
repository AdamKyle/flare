import { StatTypes } from '../../enums/stat-types';

export default interface UseStatDetailsVisibilityState {
  showStateDetails: boolean;
  statType: StatTypes | null;
}
