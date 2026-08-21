import CraftSetOutputSelection from './craft-set-output-selection';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';

export type EmptyBatchCraftingScreenProps = Record<string, never>;

export type BatchCraftingIntroductionScreenProps =
  EmptyBatchCraftingScreenProps;

export type BatchCraftingTypeScreenProps = EmptyBatchCraftingScreenProps;

export type BatchCraftingModeScreenProps = EmptyBatchCraftingScreenProps;

export type CraftAmountScreenProps = EmptyBatchCraftingScreenProps;

export type CraftExperienceScreenProps = EmptyBatchCraftingScreenProps;

export type CraftSetOutputScreenProps = EmptyBatchCraftingScreenProps;

export interface CraftSetScreenProps {
  output_selection: CraftSetOutputSelection;
}

export type CraftEventScreenProps = EmptyBatchCraftingScreenProps;

export type BatchCraftingRunningScreenProps = EmptyBatchCraftingScreenProps;

export interface BatchCraftingScreenMap {
  [BatchCraftingScreenNames.INTRODUCTION]: BatchCraftingIntroductionScreenProps;
  [BatchCraftingScreenNames.TYPE]: BatchCraftingTypeScreenProps;
  [BatchCraftingScreenNames.MODE]: BatchCraftingModeScreenProps;
  [BatchCraftingScreenNames.CRAFT_AMOUNT]: CraftAmountScreenProps;
  [BatchCraftingScreenNames.CRAFT_EXPERIENCE]: CraftExperienceScreenProps;
  [BatchCraftingScreenNames.CRAFT_SET_OUTPUT]: CraftSetOutputScreenProps;
  [BatchCraftingScreenNames.CRAFT_SET]: CraftSetScreenProps;
  [BatchCraftingScreenNames.CRAFT_EVENT]: CraftEventScreenProps;
  [BatchCraftingScreenNames.RUNNING]: BatchCraftingRunningScreenProps;
}
