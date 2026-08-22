import { ComponentType } from 'react';

import AlchemyAmountRunningSection from '../components/running/alchemy-amount-running-section';
import AlchemyExperienceRunningSection from '../components/running/alchemy-experience-running-section';
import CraftAmountRunningSection from '../components/running/craft-amount-running-section';
import CraftAndEnchantAmountRunningSection from '../components/running/craft-and-enchant-amount-running-section';
import CraftAndEnchantExperienceRunningSection from '../components/running/craft-and-enchant-experience-running-section';
import CraftAndEnchantSetRunningSection from '../components/running/craft-and-enchant-set-running-section';
import CraftEventRunningSection from '../components/running/craft-event-running-section';
import CraftExperienceRunningSection from '../components/running/craft-experience-running-section';
import CraftSetRunningSection from '../components/running/craft-set-running-section';
import EnchantEventRunningSection from '../components/running/enchant-event-running-section';
import HolyOilsSelectedItemsRunningSection from '../components/running/holy-oils-selected-items-running-section';
import HolyOilsSetRunningSection from '../components/running/holy-oils-set-running-section';
import TrinketryRunningSection from '../components/running/trinketry-running-section';
import BatchCraftingRunningSectionProps from '../components/running/types/batch-crafting-running-section-props';
import { AlchemyBatchMode } from '../enums/alchemy-batch-mode';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { CraftAndEnchantBatchMode } from '../enums/craft-and-enchant-batch-mode';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';
import { EnchantingBatchMode } from '../enums/enchanting-batch-mode';
import { HolyOilsBatchMode } from '../enums/holy-oils-batch-mode';
import { TrinketryBatchMode } from '../enums/trinketry-batch-mode';
import { buildBatchCraftingRunningSectionKey } from '../utils/batch-crafting-running-section-key';

export const batchCraftingRunningSectionRegistry: Record<
  string,
  ComponentType<BatchCraftingRunningSectionProps>
> = {
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.CRAFT,
    CraftingBatchMode.AMOUNT
  )]: CraftAmountRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.CRAFT,
    CraftingBatchMode.EXPERIENCE
  )]: CraftExperienceRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.CRAFT,
    CraftingBatchMode.SET
  )]: CraftSetRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.CRAFT,
    CraftingBatchMode.EVENT
  )]: CraftEventRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.CRAFT_AND_ENCHANT,
    CraftAndEnchantBatchMode.AMOUNT
  )]: CraftAndEnchantAmountRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.CRAFT_AND_ENCHANT,
    CraftAndEnchantBatchMode.EXPERIENCE
  )]: CraftAndEnchantExperienceRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.CRAFT_AND_ENCHANT,
    CraftAndEnchantBatchMode.SET
  )]: CraftAndEnchantSetRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.ENCHANT,
    EnchantingBatchMode.EVENT
  )]: EnchantEventRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.ALCHEMY,
    AlchemyBatchMode.AMOUNT
  )]: AlchemyAmountRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.ALCHEMY,
    AlchemyBatchMode.EXPERIENCE
  )]: AlchemyExperienceRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.HOLY_OILS,
    HolyOilsBatchMode.SELECTED_ITEMS
  )]: HolyOilsSelectedItemsRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.HOLY_OILS,
    HolyOilsBatchMode.INVENTORY_SET
  )]: HolyOilsSetRunningSection,
  [buildBatchCraftingRunningSectionKey(
    BatchCraftingType.TRINKETRY,
    TrinketryBatchMode.EXPERIENCE
  )]: TrinketryRunningSection,
};
