import UseOpenCharacterKingdomInfoModalDefinition from './definitions/use-open-location-info-modal-definition';
import UseOpenLocationInfoModalProps from './types/use-open-location-info-modal-props';
import { ModalComponentRegistrationTypes } from '../../modals/base/component-registration/modal-component-registration-types';
import { Modal } from '../../modals/base/event-types/modal';
import { useModalEmitter } from '../../modals/base/hooks/use-modal-emitter';

export const useOpenLocationInfoModal = (
  props: UseOpenLocationInfoModalProps
): UseOpenCharacterKingdomInfoModalDefinition => {
  const modalEmitter = useModalEmitter();

  const openLocationDetails = (location_id: number, location_name: string) => {
    if (!props.characterData) {
      return;
    }

    modalEmitter.emit(Modal.MODAL, ModalComponentRegistrationTypes.LOCATION, {
      is_open: true,
      title: location_name,
      character_id: props.characterData.id,
      location_id: location_id,
      allow_clicking_outside: true,
    });
  };

  return {
    openLocationDetails,
  };
};
