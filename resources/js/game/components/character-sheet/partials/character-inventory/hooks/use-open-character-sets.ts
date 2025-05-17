import UseOpenCharacterSetDefinition from './definition/use-open-character-sets-definition';
import UseOpenCharacterSetsProps from './types/use-open-character-sets-props';
import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenCharacterSets = (
  props: UseOpenCharacterSetsProps
): UseOpenCharacterSetDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openSets = () => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.SETS,
      {
        is_open: true,
        title: 'Inventory Sets',
        character_id: props.character_id,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openSets,
  };
};
