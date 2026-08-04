import GemComparisonApiResponseDefinition from '../../api/definitions/gem-comparison-api-response-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseSeerAttachGemFlowDefinition {
  slotId: number | null;
  gemSlotId: number | null;
  replaceId: number | null;
  comparison: GemComparisonApiResponseDefinition | null;
  comparisonLoading: boolean;
  error: string | null;
  addSubmitting: boolean;
  replaceSubmitting: boolean;
  canReplace: boolean;
  itemOptions: DropdownItem[];
  gemOptions: DropdownItem[];
  selectSlot: (slotId: number) => void;
  selectGemSlot: (gemSlotId: number) => void;
  selectReplaceGem: (gemId: number) => void;
  addGem: () => Promise<void>;
  replaceGem: () => Promise<void>;
}
