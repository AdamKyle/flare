import { InventoryItemTypes } from '../../../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';

export default interface EquipItemProps {
  on_close: () => void;
  character_id: number;
  slot_id: number;
  item_to_equip_type: InventoryItemTypes | null;
}
