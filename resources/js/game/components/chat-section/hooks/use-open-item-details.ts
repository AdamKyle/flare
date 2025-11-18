import UseOpenItemDetailsDefinition from './definitions/use-open-item-details-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenItemDetails = (): UseOpenItemDetailsDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openServerMessageItem = (character_id: number, slot_id: number) => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.SERVER_CHAT_ITEM,
      {
        is_open: true,
        title: 'Found Item',
        character_id,
        slot_id,
      }
    );
  };

  return {
    openServerMessageItem,
  };
};
