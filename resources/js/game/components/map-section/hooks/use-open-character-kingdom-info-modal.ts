import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseOpenCharacterKingdomInfoModalDefinition from './definitions/use-open-character-kingdom-info-modal-definition';
import UseOpenCharacterKingdomInfoModalProps from './types/use-open-character-kingdom-info-modal-props';
import { ModalComponentRegistrationTypes } from '../../modals/base/component-registration/modal-component-registration-types';
import { ModalEventMap } from '../../modals/base/event-map/modal-event-map';
import { Modal } from '../../modals/base/event-types/modal';

export const useOpenCharacterKingdomInfoModal = (
  props: UseOpenCharacterKingdomInfoModalProps
): UseOpenCharacterKingdomInfoModalDefinition => {
  const eventSystem = useEventSystem();

  const emitter = eventSystem.fetchOrCreateEventEmitter<ModalEventMap>(
    Modal.MODAL
  );

  const openCharacterKingdomDetails = (kingdom_id: number) => {
    emitter.emit(
      Modal.MODAL,
      ModalComponentRegistrationTypes.CHARACTER_KINGDOM,
      {
        is_open: true,
        title: 'Character Kingdom',
        character_id: props.character_id,
        kingdom_id: kingdom_id,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openCharacterKingdomDetails,
  };
};
