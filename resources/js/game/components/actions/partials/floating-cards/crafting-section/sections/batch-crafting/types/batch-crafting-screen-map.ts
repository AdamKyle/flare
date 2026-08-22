import CraftAndEnchantOutputSelection from './craft-and-enchant-output-selection';
import CraftAndEnchantSetSelection from './craft-and-enchant-set-selection';
import CraftSetOutputSelection from './craft-set-output-selection';
import { BatchCraftingScreenNames as Screen } from '../enums/batch-crafting-screen-names';

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

export type CraftAndEnchantModeScreenProps = EmptyBatchCraftingScreenProps;

export type CraftAndEnchantAmountOutputScreenProps =
  EmptyBatchCraftingScreenProps;

export interface CraftAndEnchantAmountScreenProps {
  output_selection: CraftAndEnchantOutputSelection;
}

export type CraftAndEnchantExperienceScreenProps =
  EmptyBatchCraftingScreenProps;

export type CraftAndEnchantSetOutputScreenProps = EmptyBatchCraftingScreenProps;

export interface CraftAndEnchantSetScreenProps {
  output_selection: CraftAndEnchantOutputSelection;
}

export interface CraftAndEnchantSetEnchantmentsScreenProps {
  set_selection: CraftAndEnchantSetSelection;
}

export type EnchantEventScreenProps = EmptyBatchCraftingScreenProps;

export type AlchemyModeScreenProps = EmptyBatchCraftingScreenProps;

export type AlchemyAmountScreenProps = EmptyBatchCraftingScreenProps;

export type AlchemyExperienceScreenProps = EmptyBatchCraftingScreenProps;

export type HolyOilsModeScreenProps = EmptyBatchCraftingScreenProps;

export type HolyOilsSelectedItemsScreenProps = EmptyBatchCraftingScreenProps;

export type HolyOilsSetScreenProps = EmptyBatchCraftingScreenProps;

export type TrinketryScreenProps = EmptyBatchCraftingScreenProps;

export type BatchCraftingRunningScreenProps = EmptyBatchCraftingScreenProps;

export interface BatchCraftingScreenMap {
  [Screen.INTRODUCTION]: BatchCraftingIntroductionScreenProps;
  [Screen.TYPE]: BatchCraftingTypeScreenProps;
  [Screen.MODE]: BatchCraftingModeScreenProps;
  [Screen.CRAFT_AMOUNT]: CraftAmountScreenProps;
  [Screen.CRAFT_EXPERIENCE]: CraftExperienceScreenProps;
  [Screen.CRAFT_SET_OUTPUT]: CraftSetOutputScreenProps;
  [Screen.CRAFT_SET]: CraftSetScreenProps;
  [Screen.CRAFT_EVENT]: CraftEventScreenProps;
  [Screen.CRAFT_AND_ENCHANT_MODE]: CraftAndEnchantModeScreenProps;
  [Screen.CRAFT_AND_ENCHANT_AMOUNT_OUTPUT]: CraftAndEnchantAmountOutputScreenProps;
  [Screen.CRAFT_AND_ENCHANT_AMOUNT]: CraftAndEnchantAmountScreenProps;
  [Screen.CRAFT_AND_ENCHANT_EXPERIENCE]: CraftAndEnchantExperienceScreenProps;
  [Screen.CRAFT_AND_ENCHANT_SET_OUTPUT]: CraftAndEnchantSetOutputScreenProps;
  [Screen.CRAFT_AND_ENCHANT_SET]: CraftAndEnchantSetScreenProps;
  [Screen.CRAFT_AND_ENCHANT_SET_ENCHANTMENTS]: CraftAndEnchantSetEnchantmentsScreenProps;
  [Screen.ENCHANT_EVENT]: EnchantEventScreenProps;
  [Screen.ALCHEMY_MODE]: AlchemyModeScreenProps;
  [Screen.ALCHEMY_AMOUNT]: AlchemyAmountScreenProps;
  [Screen.ALCHEMY_EXPERIENCE]: AlchemyExperienceScreenProps;
  [Screen.HOLY_OILS_MODE]: HolyOilsModeScreenProps;
  [Screen.HOLY_OILS_SELECTED_ITEMS]: HolyOilsSelectedItemsScreenProps;
  [Screen.HOLY_OILS_SET]: HolyOilsSetScreenProps;
  [Screen.TRINKETRY]: TrinketryScreenProps;
  [Screen.RUNNING]: BatchCraftingRunningScreenProps;
}
