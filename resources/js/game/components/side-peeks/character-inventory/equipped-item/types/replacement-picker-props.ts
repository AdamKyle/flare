import { InventoryPositionDefinition } from '../../../../character-sheet/partials/character-inventory/enums/equipment-positions';

export default interface ReplacementPickerProps {
  character_id: number;
  target_position: InventoryPositionDefinition;
  target_item_name: string;
  is_equipment_restricted: boolean;
  on_close: () => void;
  on_equipped: (successMessage: string) => void;
}
