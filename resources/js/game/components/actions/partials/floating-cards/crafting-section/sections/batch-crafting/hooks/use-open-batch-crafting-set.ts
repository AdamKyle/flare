import UseOpenBatchCraftingSetDefinition from './definitions/use-open-batch-crafting-set-definition';
import UseOpenBatchCraftingSetProps from './types/use-open-batch-crafting-set-props';
import { SidePeekComponentRegistrationEnum } from '../../../../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../../../../side-peeks/base/hooks/use-side-peek-emitter';

/**
 * Open the Inventory Sets side-peek focused on a known Craft Set output destination Set.
 */
export const useOpenBatchCraftingSet = ({
  character_id,
}: UseOpenBatchCraftingSetProps): UseOpenBatchCraftingSetDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openBatchCraftingSet = (set_id: number, set_name: string): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.SETS,
      {
        is_open: true,
        title: 'Inventory Sets',
        character_id,
        allow_clicking_outside: true,
        initial_set_id: set_id,
        initial_set_name: set_name,
      }
    );
  };

  return { openBatchCraftingSet };
};
