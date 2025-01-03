import BaseAttachedAffixesDetails from './base-attached-affixes-details';
import { BaseItemDetails } from './base-item-details';

export default interface BaseEquippedItemDetails {
  item_base_stat: number;
  item_details: BaseItemDetails;
  attached_affixes: BaseAttachedAffixesDetails[] | [];
}
