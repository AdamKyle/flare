import { InventoryItemTypes } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';

export default interface ListItemOnMarketProps {
  type: InventoryItemTypes;
  on_close: () => void;
  on_action: (message: string) => void;
  character_id: number;
  slot_id: number;
}
