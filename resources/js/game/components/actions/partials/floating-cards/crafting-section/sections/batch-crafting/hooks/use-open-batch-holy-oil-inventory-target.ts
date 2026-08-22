import UseOpenBatchHolyOilInventoryTargetDefinition from './definitions/use-open-batch-holy-oil-inventory-target-definition';
import { SidePeekComponentRegistrationEnum } from '../../../../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../../../../side-peeks/base/hooks/use-side-peek-emitter';

/**
 * Open the character's Backpack side peek, searching for the given loose Holy Oil target item's
 * exact name so the player can find it in their normal Inventory.
 */
export const useOpenBatchHolyOilInventoryTarget =
  (): UseOpenBatchHolyOilInventoryTargetDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openBatchHolyOilInventoryTarget = (itemName: string): void => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.BACKPACK,
        {
          is_open: true,
          title: 'Backpack',
          allow_clicking_outside: false,
          initial_search_text: itemName,
        }
      );
    };

    return { openBatchHolyOilInventoryTarget };
  };
