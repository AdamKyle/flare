import { BaseItemDetails } from '../../../../../../api-definitions/items/base-item-details';

export default interface CurrentlyEquippedPanelProps {
  position: string;
  equippedItem: BaseItemDetails;
  isTwoHanded: boolean;
}
