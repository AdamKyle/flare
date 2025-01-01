import { StatTypes } from '../../enums/stat-types';

export default interface UseManageStatDetailsBreakdownDefinition {
  openStatDetails: (statType: StatTypes) => void;
}
