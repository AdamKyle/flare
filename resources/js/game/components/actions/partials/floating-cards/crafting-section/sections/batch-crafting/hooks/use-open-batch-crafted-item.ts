import UseOpenBatchCraftedItemDefinition from './definitions/use-open-batch-crafted-item-definition';
import UseOpenBatchCraftedItemProps from './types/use-open-batch-crafted-item-props';
import { SidePeekComponentRegistrationEnum } from '../../../../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../../../../side-peeks/base/hooks/use-side-peek-emitter';
import { BatchCraftingBatchStatusDefinition } from '../api/definitions/batch-crafting-status-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';

/**
 * Open the factual retained destination for the current Batch Crafting item, when known.
 *
 * Falls back to the generic read-only Item Details flow whenever the item's retained
 * destination cannot be truthfully determined from the current status (Sell/Destroy/Keep
 * Best dispositions, or a Keep destination whose Set identity is not yet known).
 */
export const useOpenBatchCraftedItem = ({
  character_id,
}: UseOpenBatchCraftedItemProps): UseOpenBatchCraftedItemDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openBatchCraftedItem = (
    batch: BatchCraftingBatchStatusDefinition
  ): void => {
    if (batch.current_item_id === null || batch.current_item_name === null) {
      return;
    }

    if (
      batch.disposition === BatchCraftingDisposition.KEEP &&
      batch.output_destination === BatchCraftingOutputDestination.INVENTORY
    ) {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.BACKPACK,
        {
          is_open: true,
          title: 'Backpack',
          allow_clicking_outside: false,
          initial_search_text: batch.current_item_name,
        }
      );

      return;
    }

    if (
      batch.disposition === BatchCraftingDisposition.KEEP &&
      (batch.output_destination ===
        BatchCraftingOutputDestination.CRAFTED_ITEMS_SET ||
        batch.output_destination ===
          BatchCraftingOutputDestination.INVENTORY_SET) &&
      batch.destination_set_id !== null &&
      batch.destination_set_name !== null
    ) {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.SETS,
        {
          is_open: true,
          title: 'Inventory Sets',
          character_id,
          allow_clicking_outside: true,
          initial_search_text: batch.current_item_name,
          initial_set_id: batch.destination_set_id,
          initial_set_name: batch.destination_set_name,
        }
      );

      return;
    }

    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ITEM_DETAILS,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: batch.current_item_id,
      }
    );
  };

  return { openBatchCraftedItem };
};
