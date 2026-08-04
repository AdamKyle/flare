import SeerItemDefinition from '../../api/definitions/seer-item-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseSeerManageSocketsFlowDefinition {
  selectedItem: SeerItemDefinition | null;
  options: DropdownItem[];
  submitting: boolean;
  error: string | null;
  canSubmit: boolean;
  selectItem: (slotId: number) => void;
  submit: () => Promise<void>;
}
