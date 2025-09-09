import { BaseItemDetails } from './base-item-details';
import ItemAffixDefinition from './equippable-item-definitions/item-affix-definition';

export default interface BaseEquippedItemDetails {
  item_base_stat: number;
  item_details: BaseItemDetails;
  attached_affixes: ItemAffixDefinition[] | [];
}
