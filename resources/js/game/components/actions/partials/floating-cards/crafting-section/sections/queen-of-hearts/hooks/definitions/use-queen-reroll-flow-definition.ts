import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import QueenCostDefinition from '../../api/definitions/queen-cost-definition';
import QueenInventorySlotDefinition from '../../api/definitions/queen-inventory-slot-definition';
import QueenOfHeartsApiResponseDefinition from '../../api/definitions/queen-of-hearts-api-response-definition';
import UseQueenUniqueItemsApiDefinition from '../../api/hooks/definitions/use-queen-unique-items-api-definition';
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
  itemsApi: UseQueenUniqueItemsApiDefinition;
  affixOptions: DropdownItem[];
  rerollTypeOptions: DropdownItem[];
  selectedSlotId: number | null;
  selectedSlot: QueenInventorySlotDefinition | null;
  selectedAffix: QueenAffixSelection | null;
  selectedRerollType: QueenRerollType | null;
  selectedCost: QueenCostDefinition | null;
  resultPreview: CraftingItemPreviewDefinition | null;
  submitting: boolean;
  error: string | null;
  canSubmit: boolean;
  handleSelectSlot: (option: DropdownItem) => void;
  handleSelectAffix: (option: DropdownItem) => void;
  handleSelectRerollType: (option: DropdownItem) => void;
  handleSubmit: () => Promise<void>;
}
