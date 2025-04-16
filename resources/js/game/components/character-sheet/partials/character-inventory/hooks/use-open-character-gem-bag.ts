import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseCharacterGemBagDefinition from './definition/use-character-gem-bag-definition';
import UseOpenCharacterGemBagProps from './types/use-open-character-gem-bag-props';
import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeekEventMap } from '../../../../side-peeks/base/event-map/side-peek-event-map';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';

export const useOpenCharacterGemBag = (
  props: UseOpenCharacterGemBagProps
): UseCharacterGemBagDefinition => {
  const eventSystem = useEventSystem();

  const emitter = eventSystem.fetchOrCreateEventEmitter<SidePeekEventMap>(
    SidePeek.SIDE_PEEK
  );

  const openGemBag = () => {
    emitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.GEM_BAG,
      {
        is_open: true,
        title: 'Gem Bag',
        character_id: props.character_id,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openGemBag,
  };
};
