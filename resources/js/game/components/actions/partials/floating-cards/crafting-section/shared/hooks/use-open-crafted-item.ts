import UseOpenCraftedItemDefinition from './definitions/use-open-crafted-item-definition';
import BaseGemDetails from '../../../../../../../api-definitions/items/base-gem-details';
import BaseUsableItemDefinition from '../../../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import { SidePeekComponentRegistrationEnum } from '../../../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../../../side-peeks/base/hooks/use-side-peek-emitter';
import { CraftedItemKind } from '../../../../../../side-peeks/crafted-item/enums/crafted-item-kind';

export const useOpenCraftedItem = (): UseOpenCraftedItemDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openCraftedInventoryItem = (
    characterId: number,
    slotId: number
  ): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.CRAFTED_ITEM,
      {
        is_open: true,
        title: 'Crafted Item',
        kind: CraftedItemKind.INVENTORY,
        character_id: characterId,
        slot_id: slotId,
      }
    );
  };

  const openCraftedUsableItem = (item: BaseUsableItemDefinition): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.CRAFTED_ITEM,
      {
        is_open: true,
        title: 'Crafted Item',
        kind: CraftedItemKind.USABLE,
        item,
      }
    );
  };

  const openCraftedGem = (gem: BaseGemDetails): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.CRAFTED_ITEM,
      {
        is_open: true,
        title: 'Crafted Item',
        kind: CraftedItemKind.GEM,
        gem,
      }
    );
  };

  return {
    openCraftedInventoryItem,
    openCraftedUsableItem,
    openCraftedGem,
  };
};
