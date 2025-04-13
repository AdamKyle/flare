import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BackPack from '../../character-inventory/backpack/backpack';
import BackPackProps from '../../character-inventory/backpack/types/backpack-props';
import GemBag from '../../character-inventory/gem-bag/gem-bag';
import GemBagProps from '../../character-inventory/gem-bag/types/gem-bag-props';

export const SidePeekComponentRegistry = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: {
    component: BackPack,
    props: {} as BackPackProps,
  },
  [SidePeekComponentRegistrationEnum.GEM_BAG]: {
    component: GemBag,
    props: {} as GemBagProps,
  },
  // Add more components here
};
