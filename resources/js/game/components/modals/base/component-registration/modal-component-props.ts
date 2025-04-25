import { ModalComponentRegistrationTypes } from './modal-component-registration-types';
import CharacterKingdomDetailsProps from '../../kingdom-modals/types/character-kingdom-details-props';

import ModalProps from 'ui/modal/types/modal-props';

export type ModalComponentPropsMap = {
  [ModalComponentRegistrationTypes.CHARACTER_KINGDOM]: CharacterKingdomDetailsProps;
  // Add More ...
};

/**
 * Enforces at compile time that every modal extends the Modal Props from the UI component.
 */
// eslint-disable-next-line @typescript-eslint/no-unused-vars
type _ValidateAllModalProps = {
  [K in keyof ModalComponentPropsMap]: ModalComponentPropsMap[K] extends ModalProps
    ? true
    : never;
};
