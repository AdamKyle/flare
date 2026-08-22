import { ScreenRegistry } from 'screen-manager/types/registery-type';

import BatchCraftingRunningPanel from '../components/batch-crafting-running-panel';
import CraftAmountForm from '../components/craft-amount-form';
import CraftEventForm from '../components/craft-event-form';
import CraftExperienceForm from '../components/craft-experience-form';
import CraftSetForm from '../components/craft-set-form';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import AlchemyAmountScreen from '../screens/alchemy-amount-screen';
import AlchemyExperienceScreen from '../screens/alchemy-experience-screen';
import AlchemyModeScreen from '../screens/alchemy-mode-screen';
import BatchCraftingIntroductionScreen from '../screens/batch-crafting-introduction-screen';
import BatchCraftingModeScreen from '../screens/batch-crafting-mode-screen';
import BatchCraftingTypeScreen from '../screens/batch-crafting-type-screen';
import CraftAndEnchantAmountOutputScreen from '../screens/craft-and-enchant-amount-output-screen';
import CraftAndEnchantAmountScreen from '../screens/craft-and-enchant-amount-screen';
import CraftAndEnchantExperienceScreen from '../screens/craft-and-enchant-experience-screen';
import CraftAndEnchantModeScreen from '../screens/craft-and-enchant-mode-screen';
import CraftAndEnchantSetEnchantmentsScreen from '../screens/craft-and-enchant-set-enchantments-screen';
import CraftAndEnchantSetOutputScreen from '../screens/craft-and-enchant-set-output-screen';
import CraftAndEnchantSetScreen from '../screens/craft-and-enchant-set-screen';
import CraftSetOutputScreen from '../screens/craft-set-output-screen';
import EnchantEventScreen from '../screens/enchant-event-screen';
import HolyOilsModeScreen from '../screens/holy-oils-mode-screen';
import HolyOilsSelectedItemsScreen from '../screens/holy-oils-selected-items-screen';
import HolyOilsSetScreen from '../screens/holy-oils-set-screen';
import TrinketryScreen from '../screens/trinketry-screen';
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
    [BatchCraftingScreenNames.CRAFT_AND_ENCHANT_MODE]:
      CraftAndEnchantModeScreen,
    [BatchCraftingScreenNames.CRAFT_AND_ENCHANT_AMOUNT_OUTPUT]:
      CraftAndEnchantAmountOutputScreen,
    [BatchCraftingScreenNames.CRAFT_AND_ENCHANT_AMOUNT]:
      CraftAndEnchantAmountScreen,
    [BatchCraftingScreenNames.CRAFT_AND_ENCHANT_EXPERIENCE]:
      CraftAndEnchantExperienceScreen,
    [BatchCraftingScreenNames.CRAFT_AND_ENCHANT_SET_OUTPUT]:
      CraftAndEnchantSetOutputScreen,
    [BatchCraftingScreenNames.CRAFT_AND_ENCHANT_SET]: CraftAndEnchantSetScreen,
    [BatchCraftingScreenNames.CRAFT_AND_ENCHANT_SET_ENCHANTMENTS]:
      CraftAndEnchantSetEnchantmentsScreen,
    [BatchCraftingScreenNames.ENCHANT_EVENT]: EnchantEventScreen,
    [BatchCraftingScreenNames.ALCHEMY_MODE]: AlchemyModeScreen,
    [BatchCraftingScreenNames.ALCHEMY_AMOUNT]: AlchemyAmountScreen,
    [BatchCraftingScreenNames.ALCHEMY_EXPERIENCE]: AlchemyExperienceScreen,
    [BatchCraftingScreenNames.HOLY_OILS_MODE]: HolyOilsModeScreen,
    [BatchCraftingScreenNames.HOLY_OILS_SELECTED_ITEMS]:
      HolyOilsSelectedItemsScreen,
    [BatchCraftingScreenNames.HOLY_OILS_SET]: HolyOilsSetScreen,
    [BatchCraftingScreenNames.TRINKETRY]: TrinketryScreen,
    [BatchCraftingScreenNames.RUNNING]: BatchCraftingRunningPanel,
  };
