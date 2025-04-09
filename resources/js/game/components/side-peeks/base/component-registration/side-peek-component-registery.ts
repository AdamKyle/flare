import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BackPack from '../../character-inventory/backpack/backpack';
import BackPackProps from '../../character-inventory/backpack/types/backpack-props';

export const SidePeekComponentRegistry = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: {
    component: BackPack,
    props: {} as BackPackProps,
  },
  // Add more components here
};
