import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BackPackProps from '../../character-inventory/backpack/types/backpack-props';
import GemBagProps from '../../character-inventory/gem-bag/types/gem-bag-props';

export type SidePeekComponentPropsMap = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: BackPackProps;
  [SidePeekComponentRegistrationEnum.GEM_BAG]: GemBagProps;
  // Future components go here
};
