import { InventoryItemTypes } from '../../../../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';

export default interface CurrentlyEquippedPanelProps {
  position: string;
  equippedAffixName: string;
  type?: InventoryItemTypes;
  isTwoHanded?: boolean;
}
