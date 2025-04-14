import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseOpenCharacterUsableInventoryDefinition from './definition/use-open-character-usable-inventory-definition';
import UseOpenCharacterUsableInventoryProps from './types/use-open-character-uable-inventory-props';
import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeekEventMap } from '../../../../side-peeks/base/event-map/side-peek-event-map';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';

export const useOpenCharacterUsableInventory = (
  props: UseOpenCharacterUsableInventoryProps
): UseOpenCharacterUsableInventoryDefinition => {
  const eventSystem = useEventSystem();

  const emitter = eventSystem.fetchOrCreateEventEmitter<SidePeekEventMap>(
    SidePeek.SIDE_PEEK
  );

  const openUsableInventory = () => {
    emitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.USABLE_ITEMS,
      {
        is_open: true,
        title: 'Usable Items',
        character_id: props.character_id,
      }
    );
  };

  return {
    openUsableInventory,
  };
};
