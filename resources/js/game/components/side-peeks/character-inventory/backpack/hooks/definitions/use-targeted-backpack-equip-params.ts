import { InventoryPositionDefinition } from '../../../../../character-sheet/partials/character-inventory/enums/equipment-positions';

export default interface UseTargetedBackpackEquipParams {
  character_id: number;
  target_position?: InventoryPositionDefinition;
  on_equipment_changed?: () => void;
  refresh_backpack: () => void;
  close_item_detail: () => void;
  set_success_message: (message: string | null) => void;
}
