import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { EquippableItemWithBase } from '../../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

export default interface UseTargetedBackpackEquipDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  restriction_message: string | null;
  is_restricted: boolean;
  resetFeedback: () => void;
  equipSelectedItem: (selectedItem: EquippableItemWithBase) => void;
}
