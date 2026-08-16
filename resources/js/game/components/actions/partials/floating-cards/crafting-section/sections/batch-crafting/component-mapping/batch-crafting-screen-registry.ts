import { ScreenRegistry } from 'screen-manager/types/registery-type';

import BatchCraftingRunningPanel from '../components/batch-crafting-running-panel';
import CraftAmountForm from '../components/craft-amount-form';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import BatchCraftingEntryScreen from '../screens/batch-crafting-entry-screen';
import BatchCraftingIntroductionScreen from '../screens/batch-crafting-introduction-screen';
import BatchCraftingModeScreen from '../screens/batch-crafting-mode-screen';
import BatchCraftingTypeScreen from '../screens/batch-crafting-type-screen';
import { BatchCraftingScreenMap } from '../types/batch-crafting-screen-map';

export const batchCraftingScreenRegistry: ScreenRegistry<BatchCraftingScreenMap> =
  {
    [BatchCraftingScreenNames.ENTRY]: BatchCraftingEntryScreen,
    [BatchCraftingScreenNames.INTRODUCTION]: BatchCraftingIntroductionScreen,
    [BatchCraftingScreenNames.TYPE]: BatchCraftingTypeScreen,
    [BatchCraftingScreenNames.MODE]: BatchCraftingModeScreen,
    [BatchCraftingScreenNames.CRAFT_AMOUNT]: CraftAmountForm,
    [BatchCraftingScreenNames.RUNNING]: BatchCraftingRunningPanel,
  };
