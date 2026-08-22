import UseOpenBatchCraftedAlchemyItemDefinition from './definitions/use-open-batch-crafted-alchemy-item-definition';
import UseOpenBatchCraftedAlchemyItemProps from './types/use-open-batch-crafted-alchemy-item-props';
import { SidePeekComponentRegistrationEnum } from '../../../../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../../../../side-peeks/base/hooks/use-side-peek-emitter';

/**
 * Open the character's Usable Items/Alchemy Bag side peek, searching for the given retained
 * Alchemy item's exact name so the player can find it in the Bag.
 */
export const useOpenBatchCraftedAlchemyItem = ({
  character_id,
}: UseOpenBatchCraftedAlchemyItemProps): UseOpenBatchCraftedAlchemyItemDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openBatchCraftedAlchemyItem = (itemName: string): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.USABLE_ITEMS,
      {
        is_open: true,
        title: 'Usable Items',
        character_id,
        allow_clicking_outside: true,
        initial_search_text: itemName,
      }
    );
  };

  return { openBatchCraftedAlchemyItem };
};
