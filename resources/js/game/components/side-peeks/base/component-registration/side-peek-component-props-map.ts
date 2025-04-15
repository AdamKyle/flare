import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BackPackProps from '../../character-inventory/backpack/types/backpack-props';
import GemBagProps from '../../character-inventory/gem-bag/types/gem-bag-props';
import SetsProps from '../../character-inventory/sets/types/sets-props';
import UsableItemsProps from '../../character-inventory/usable-items/types/usable-items-props';

export type SidePeekComponentPropsMap = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: BackPackProps;
  [SidePeekComponentRegistrationEnum.GEM_BAG]: GemBagProps;
  [SidePeekComponentRegistrationEnum.USABLE_ITEMS]: UsableItemsProps;
  [SidePeekComponentRegistrationEnum.SETS]: SetsProps;
  // Future components go here
};
