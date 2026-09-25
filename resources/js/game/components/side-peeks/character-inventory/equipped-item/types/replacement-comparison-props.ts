import { EquippableItemWithBase } from '../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { InventoryPositionDefinition } from '../../../../character-sheet/partials/character-inventory/enums/equipment-positions';

export default interface ReplacementComparisonProps {
  character_id: number;
  candidate: EquippableItemWithBase;
  target_position: InventoryPositionDefinition;
  is_equipment_restricted: boolean;
  on_close: () => void;
  on_equipped: (successMessage: string) => void;
}
