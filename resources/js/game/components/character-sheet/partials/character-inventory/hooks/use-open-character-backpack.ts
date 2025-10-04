import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenCharacterBackpack = () => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openBackpack = () => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.BACKPACK,
      {
        is_open: true,
        title: 'Backpack',
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openBackpack,
  };
};
