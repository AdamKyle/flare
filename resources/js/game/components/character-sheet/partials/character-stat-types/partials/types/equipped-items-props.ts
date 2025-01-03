import BaseEquippedItemDetails from '../../../../../../api-definitions/items/base-equipped-item-details';
import { StatTypes } from '../../../../enums/stat-types';

export default interface EquippedItemProps {
  items_equipped: BaseEquippedItemDetails[] | [];
  stat_type: StatTypes;
}
