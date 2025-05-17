import UseOpenCharacterKingdomInfoModalDefinition from './definitions/use-open-character-kingdom-info-modal-definition';
import UseOpenCharacterKingdomInfoModalProps from './types/use-open-character-kingdom-info-modal-props';
import { ModalComponentRegistrationTypes } from '../../modals/base/component-registration/modal-component-registration-types';
import { Modal } from '../../modals/base/event-types/modal';
import { useModalEmitter } from '../../modals/base/hooks/use-modal-emitter';

export const useOpenCharacterKingdomInfoModal = (
  props: UseOpenCharacterKingdomInfoModalProps
): UseOpenCharacterKingdomInfoModalDefinition => {
  const modalEmitter = useModalEmitter();

  const openCharacterKingdomDetails = (
    kingdom_id: number,
    kingdom_name: string
  ) => {
    if (!props.characterData) {
      return;
    }

    modalEmitter.emit(
      Modal.MODAL,
      ModalComponentRegistrationTypes.CHARACTER_KINGDOM,
      {
        is_open: true,
        title: kingdom_name,
        character_id: props.characterData.id,
        kingdom_id: kingdom_id,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openCharacterKingdomDetails,
  };
};
