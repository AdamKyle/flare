import UseOpenCharacterUsableInventoryDefinition from './definition/use-open-character-usable-inventory-definition';
import UseOpenCharacterUsableInventoryProps from './types/use-open-character-uable-inventory-props';
import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenCharacterUsableInventory = (
  props: UseOpenCharacterUsableInventoryProps
): UseOpenCharacterUsableInventoryDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openUsableInventory = () => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.USABLE_ITEMS,
      {
        is_open: true,
        title: 'Usable Items',
        character_id: props.character_id,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openUsableInventory,
  };
};
