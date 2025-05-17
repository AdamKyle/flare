import UseOpenCharacterBackpackProps from './types/use-open-character-backpack-props';
import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenCharacterBackpack = (
  props: UseOpenCharacterBackpackProps
) => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openBackpack = () => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.BACKPACK,
      {
        is_open: true,
        title: 'Backpack',
        character_id: props.character_id,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openBackpack,
  };
};
