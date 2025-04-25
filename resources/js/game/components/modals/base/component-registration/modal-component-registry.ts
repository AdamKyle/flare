import React from 'react';

import { ModalComponentPropsMap } from './modal-component-props';
import { ModalComponentRegistrationTypes } from './modal-component-registration-types';
import CharacterKingdomDetails from '../../kingdom-modals/character-kingdom-details';
import CharacterKingdomDetailsProps from '../../kingdom-modals/types/character-kingdom-details-props';

import ModalProps from 'ui/modal/types/modal-props';

export const ModalComponentRegistry: {
  [K in keyof ModalComponentPropsMap]: {
    component: React.ComponentType<ModalComponentPropsMap[K]>;
    props: ModalProps;
  };
} = {
  [ModalComponentRegistrationTypes.CHARACTER_KINGDOM]: {
    component: CharacterKingdomDetails,
    props: {} as CharacterKingdomDetailsProps,
  },
};
