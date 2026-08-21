import { ComponentType } from 'react';

import CraftAmountRunningSection from '../components/running/craft-amount-running-section';
import CraftEventRunningSection from '../components/running/craft-event-running-section';
import CraftExperienceRunningSection from '../components/running/craft-experience-running-section';
import CraftSetRunningSection from '../components/running/craft-set-running-section';
import BatchCraftingRunningSectionProps from '../components/running/types/batch-crafting-running-section-props';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';

export const batchCraftingRunningSectionRegistry: Record<
  CraftingBatchMode,
  ComponentType<BatchCraftingRunningSectionProps>
> = {
  [CraftingBatchMode.AMOUNT]: CraftAmountRunningSection,
  [CraftingBatchMode.EXPERIENCE]: CraftExperienceRunningSection,
  [CraftingBatchMode.SET]: CraftSetRunningSection,
  [CraftingBatchMode.EVENT]: CraftEventRunningSection,
};
