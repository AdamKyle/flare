import BaseEquippedItemDetails from '../../../../../../api-definitions/items/base-equipped-item-details';
import { StatTypes } from '../../../../enums/stat-types';

export default interface EquippedItemProps {
  equipped_item: BaseEquippedItemDetails;
  stat_type: StatTypes;
}
