import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BackPack from '../../character-inventory/backpack/backpack';
import BackPackProps from '../../character-inventory/backpack/types/backpack-props';
import GemBag from '../../character-inventory/gem-bag/gem-bag';
import GemBagProps from '../../character-inventory/gem-bag/types/gem-bag-props';
import Sets from '../../character-inventory/sets/sets';
import SetsProps from '../../character-inventory/sets/types/sets-props';
import UsableItemsProps from '../../character-inventory/usable-items/types/usable-items-props';
import UsableItems from '../../character-inventory/usable-items/usable-items';

export const SidePeekComponentRegistry = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: {
    component: BackPack,
    props: {} as BackPackProps,
  },
  [SidePeekComponentRegistrationEnum.GEM_BAG]: {
    component: GemBag,
    props: {} as GemBagProps,
  },
  [SidePeekComponentRegistrationEnum.USABLE_ITEMS]: {
    component: UsableItems,
    props: {} as UsableItemsProps,
  },
  [SidePeekComponentRegistrationEnum.SETS]: {
    component: Sets,
    props: {} as SetsProps,
  },
  // Add more components here
};
