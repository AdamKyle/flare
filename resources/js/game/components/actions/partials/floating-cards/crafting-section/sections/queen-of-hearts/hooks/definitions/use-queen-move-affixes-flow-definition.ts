import QueenCostDefinition from '../../api/definitions/queen-cost-definition';
import QueenOfHeartsApiResponseDefinition from '../../api/definitions/queen-of-hearts-api-response-definition';
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
  sourceOptions: DropdownItem[];
  destinationOptions: DropdownItem[];
  affixOptions: DropdownItem[];
  selectedSourceId: number | null;
  selectedDestinationId: number | null;
  selectedAffix: QueenAffixSelection | null;
  selectedCost: QueenCostDefinition | null;
  submitting: boolean;
  error: string | null;
  canSubmit: boolean;
  handleSelectSource: (option: DropdownItem) => void;
  handleSelectAffix: (option: DropdownItem) => void;
  handleSelectDestination: (option: DropdownItem) => void;
  handleSubmit: () => Promise<void>;
}
