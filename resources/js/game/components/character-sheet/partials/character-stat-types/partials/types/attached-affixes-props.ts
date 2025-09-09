import ItemAffixDefinition from '../../../../../../api-definitions/items/equippable-item-definitions/item-affix-definition';
import { StatTypes } from '../../../../enums/stat-types';

export default interface AttachedAffixesProps {
  attached_affixes: ItemAffixDefinition[] | [];
  stat_type: StatTypes;
}
