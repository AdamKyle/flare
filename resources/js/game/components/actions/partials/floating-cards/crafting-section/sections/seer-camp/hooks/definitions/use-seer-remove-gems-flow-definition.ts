import SeerGemRemovalItemDefinition, {
  SeerAtonementChangeDefinition,
} from '../../api/definitions/seer-gem-removal-item-definition';
import SeerItemDefinition from '../../api/definitions/seer-item-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseSeerRemoveGemsFlowDefinition {
  slotId: number | null;
  gemId: number | null;
  selectedItem: SeerItemDefinition | null;
  selectedDetails: SeerGemRemovalItemDefinition | null;
  selectedChange: SeerAtonementChangeDefinition | null;
  itemOptions: DropdownItem[];
  gemOptions: DropdownItem[];
  isRemovingOne: boolean;
  isRemovingAll: boolean;
  isSubmitting: boolean;
  error: string | null;
  selectItem: (slotId: number) => void;
  clearItem: () => void;
  selectGem: (gemId: number) => void;
  clearGem: () => void;
  removeOne: () => Promise<void>;
  removeAll: () => Promise<void>;
}
