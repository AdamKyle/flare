import QueenCostDefinition from '../../api/definitions/queen-cost-definition';
import QueenOfHeartsApiResponseDefinition from '../../api/definitions/queen-of-hearts-api-response-definition';
import { QueenAffixSelection } from '../../enums/queen-affix-selection';
import { QueenRerollType } from '../../enums/queen-reroll-type';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export interface UseQueenRerollFlowParams {
  characterId: number;
  data: QueenOfHeartsApiResponseDefinition;
  onDataReplaced: (data: QueenOfHeartsApiResponseDefinition) => void;
  onSuccess: (message: string | undefined) => void;
}

export default interface UseQueenRerollFlowDefinition {
  hasSlots: boolean;
  slotOptions: DropdownItem[];
  affixOptions: DropdownItem[];
  rerollTypeOptions: DropdownItem[];
  selectedSlotId: number | null;
  selectedAffix: QueenAffixSelection | null;
  selectedRerollType: QueenRerollType | null;
  selectedCost: QueenCostDefinition | null;
  submitting: boolean;
  error: string | null;
  canSubmit: boolean;
  handleSelectSlot: (option: DropdownItem) => void;
  handleSelectAffix: (option: DropdownItem) => void;
  handleSelectRerollType: (option: DropdownItem) => void;
  handleSubmit: () => Promise<void>;
}
