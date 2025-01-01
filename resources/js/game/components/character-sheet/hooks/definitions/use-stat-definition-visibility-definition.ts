import { StatTypes } from '../../enums/stat-types';

export default interface UseStatDefinitionVisibilityDefinition {
  showStatDetails: boolean;
  statType: StatTypes | null;
  closeStatDetails: () => void;
}
