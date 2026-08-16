import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';

export type EmptyBatchCraftingScreenProps = Record<string, never>;

export type BatchCraftingEntryScreenProps = EmptyBatchCraftingScreenProps;

export type BatchCraftingIntroductionScreenProps =
  EmptyBatchCraftingScreenProps;

export type BatchCraftingTypeScreenProps = EmptyBatchCraftingScreenProps;

export type BatchCraftingModeScreenProps = EmptyBatchCraftingScreenProps;

export type CraftAmountScreenProps = EmptyBatchCraftingScreenProps;

export type BatchCraftingRunningScreenProps = EmptyBatchCraftingScreenProps;

export interface BatchCraftingScreenMap {
  [BatchCraftingScreenNames.ENTRY]: BatchCraftingEntryScreenProps;
  [BatchCraftingScreenNames.INTRODUCTION]: BatchCraftingIntroductionScreenProps;
  [BatchCraftingScreenNames.TYPE]: BatchCraftingTypeScreenProps;
  [BatchCraftingScreenNames.MODE]: BatchCraftingModeScreenProps;
  [BatchCraftingScreenNames.CRAFT_AMOUNT]: CraftAmountScreenProps;
  [BatchCraftingScreenNames.RUNNING]: BatchCraftingRunningScreenProps;
}
