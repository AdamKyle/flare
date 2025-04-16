import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseOpenCharacterSetDefinition from './definition/use-open-character-sets-definition';
import UseOpenCharacterSetsProps from './types/use-open-character-sets-props';
import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeekEventMap } from '../../../../side-peeks/base/event-map/side-peek-event-map';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';

export const useOpenCharacterSets = (
  props: UseOpenCharacterSetsProps
): UseOpenCharacterSetDefinition => {
  const eventSystem = useEventSystem();

  const emitter = eventSystem.fetchOrCreateEventEmitter<SidePeekEventMap>(
    SidePeek.SIDE_PEEK
  );

  const openSets = () => {
    emitter.emit(SidePeek.SIDE_PEEK, SidePeekComponentRegistrationEnum.SETS, {
      is_open: true,
      title: 'Inventory Sets',
      character_id: props.character_id,
      allow_clicking_outside: true,
    });
  };

  return {
    openSets,
  };
};
