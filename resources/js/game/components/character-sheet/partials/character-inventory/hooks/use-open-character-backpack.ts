import { useEventSystem } from 'event-system/hooks/use-event-system';

import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';

export const useOpenCharacterBackpack = () => {
  const eventSystem = useEventSystem();

  const emitter = eventSystem.fetchOrCreateEventEmitter<{
    [SidePeek.SIDE_PEEK]: [
      SidePeekComponentRegistrationEnum,
      {
        is_open: boolean;
        on_close: () => void;
        allow_clicking_outside: boolean;
        title: string;
      },
    ];
  }>(SidePeek.SIDE_PEEK);

  const openBackpack = () => {
    emitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.BACKPACK,
      {
        is_open: true,
        on_close: () => {
          console.log('Closed from on_close callback');
        },
        allow_clicking_outside: true,
        title: 'Peek-a-boo!',
      }
    );
  };

  return {
    openBackpack,
  };
};
