import { CraftAndEnchantBatchMode } from '../enums/craft-and-enchant-batch-mode';
import { CraftSetPosition } from '../enums/craft-set-position';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';

export const isCraftSetPosition = (
  position: string
): position is CraftSetPosition =>
  Object.values<string>(CraftSetPosition).includes(position);

export const isCraftAndEnchantBatchMode = (
  mode: string
): mode is CraftAndEnchantBatchMode =>
  Object.values<string>(CraftAndEnchantBatchMode).includes(mode);

export const isCraftingBatchMode = (mode: string): mode is CraftingBatchMode =>
  Object.values<string>(CraftingBatchMode).includes(mode);
