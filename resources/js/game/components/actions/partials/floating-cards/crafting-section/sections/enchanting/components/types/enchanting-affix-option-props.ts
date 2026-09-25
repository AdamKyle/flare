import EnchantingAffixDefinition from '../../api/definitions/enchanting-affix-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface EnchantingAffixOptionProps {
  option: DropdownItem;
  affixes: EnchantingAffixDefinition[];
}
