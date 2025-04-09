import { useEventSystem } from 'event-system/hooks/use-event-system';

import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeekEventMap } from '../../../../side-peeks/base/event-map/side-peek-event-map';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';

export const useOpenCharacterBackpack = () => {
  const eventSystem = useEventSystem();

  const emitter = eventSystem.fetchOrCreateEventEmitter<SidePeekEventMap>(
    SidePeek.SIDE_PEEK
  );

  const openBackpack = () => {
    emitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.BACKPACK,
      {
        is_open: true,
        title: 'Backpack',
        character_id: 0,
      }
    );
  };

  return {
    openBackpack,
  };
};
