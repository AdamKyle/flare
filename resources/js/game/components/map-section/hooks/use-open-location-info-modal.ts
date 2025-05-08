import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseOpenCharacterKingdomInfoModalDefinition from './definitions/use-open-location-info-modal-definition';
import UseOpenLocationInfoModalProps from './types/use-open-location-info-modal-props';
import { ModalComponentRegistrationTypes } from '../../modals/base/component-registration/modal-component-registration-types';
import { ModalEventMap } from '../../modals/base/event-map/modal-event-map';
import { Modal } from '../../modals/base/event-types/modal';

export const useOpenLocationInfoModal = (
  props: UseOpenLocationInfoModalProps
): UseOpenCharacterKingdomInfoModalDefinition => {
  const eventSystem = useEventSystem();

  const emitter = eventSystem.fetchOrCreateEventEmitter<ModalEventMap>(
    Modal.MODAL
  );

  const openLocationDetails = (location_id: number, location_name: string) => {
    if (!props.characterData) {
      return;
    }

    emitter.emit(
      Modal.MODAL,
      ModalComponentRegistrationTypes.CHARACTER_KINGDOM,
      {
        is_open: true,
        title: location_name,
        character_id: props.characterData.id,
        location_id: location_id,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openLocationDetails,
  };
};
