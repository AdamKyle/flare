import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BackPackProps from '../../character-inventory/backpack/types/backpack-props';

export type SidePeekComponentPropsMap = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: BackPackProps;
  // Future components go here
};
