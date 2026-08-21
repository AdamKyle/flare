import { ScreenRegistry } from 'screen-manager/types/registery-type';

import BatchCraftingRunningPanel from '../components/batch-crafting-running-panel';
import CraftAmountForm from '../components/craft-amount-form';
import CraftEventForm from '../components/craft-event-form';
import CraftExperienceForm from '../components/craft-experience-form';
import CraftSetForm from '../components/craft-set-form';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import BatchCraftingIntroductionScreen from '../screens/batch-crafting-introduction-screen';
import BatchCraftingModeScreen from '../screens/batch-crafting-mode-screen';
import BatchCraftingTypeScreen from '../screens/batch-crafting-type-screen';
import CraftSetOutputScreen from '../screens/craft-set-output-screen';
import { BatchCraftingScreenMap } from '../types/batch-crafting-screen-map';

export const batchCraftingScreenRegistry: ScreenRegistry<BatchCraftingScreenMap> =
  {
    [BatchCraftingScreenNames.INTRODUCTION]: BatchCraftingIntroductionScreen,
    [BatchCraftingScreenNames.TYPE]: BatchCraftingTypeScreen,
    [BatchCraftingScreenNames.MODE]: BatchCraftingModeScreen,
    [BatchCraftingScreenNames.CRAFT_AMOUNT]: CraftAmountForm,
    [BatchCraftingScreenNames.CRAFT_EXPERIENCE]: CraftExperienceForm,
    [BatchCraftingScreenNames.CRAFT_SET_OUTPUT]: CraftSetOutputScreen,
    [BatchCraftingScreenNames.CRAFT_SET]: CraftSetForm,
    [BatchCraftingScreenNames.CRAFT_EVENT]: CraftEventForm,
    [BatchCraftingScreenNames.RUNNING]: BatchCraftingRunningPanel,
  };
