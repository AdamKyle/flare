import UseOpenCharacterKingdomInfoModalDefinition from './definitions/use-open-character-kingdom-info-modal-definition';
import UseOpenCharacterKingdomInfoModalProps from './types/use-open-character-kingdom-info-modal-props';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenCharacterKingdomInfoSidePeek = (
  props: UseOpenCharacterKingdomInfoModalProps
): UseOpenCharacterKingdomInfoModalDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openCharacterKingdomDetails = (
    kingdom_id: number,
    kingdom_name: string
  ) => {
    if (!props.characterData) {
      return;
    }

    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.CHARACTER_KINGDOM_DETAILS,
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
