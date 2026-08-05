import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import QueenCostDefinition from '../../api/definitions/queen-cost-definition';
import QueenInventorySlotDefinition from '../../api/definitions/queen-inventory-slot-definition';
import QueenOfHeartsApiResponseDefinition from '../../api/definitions/queen-of-hearts-api-response-definition';
import UseQueenDestinationItemsApiDefinition from '../../api/hooks/definitions/use-queen-destination-items-api-definition';
import UseQueenUniqueItemsApiDefinition from '../../api/hooks/definitions/use-queen-unique-items-api-definition';
import { QueenAffixSelection } from '../../enums/queen-affix-selection';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export interface UseQueenMoveAffixesFlowParams {
  characterId: number;
  data: QueenOfHeartsApiResponseDefinition;
  onDataReplaced: (data: QueenOfHeartsApiResponseDefinition) => void;
  onSuccess: (message: string | undefined) => void;
}

export default interface UseQueenMoveAffixesFlowDefinition {
  hasSourceSlots: boolean;
  sourceItemsApi: UseQueenUniqueItemsApiDefinition;
  destinationItemsApi: UseQueenDestinationItemsApiDefinition;
  selectedSource: QueenInventorySlotDefinition | null;
  selectedDestination: QueenInventorySlotDefinition | null;
  affixOptions: DropdownItem[];
  selectedSourceId: number | null;
  selectedDestinationId: number | null;
  selectedAffix: QueenAffixSelection | null;
  selectedCost: QueenCostDefinition | null;
  sourceResultPreview: CraftingItemPreviewDefinition | null;
  destinationResultPreview: CraftingItemPreviewDefinition | null;
  submitting: boolean;
  error: string | null;
  canSubmit: boolean;
  handleSelectSource: (option: DropdownItem) => void;
  handleSelectAffix: (option: DropdownItem) => void;
  handleSelectDestination: (option: DropdownItem) => void;
  handleSubmit: () => Promise<void>;
}
