import { CraftingActionPreviewStatus } from '../types/crafting-action-preview-props';

export const STATUS_BORDER_STYLES: Record<CraftingActionPreviewStatus, string> = {
  default: 'border-gray-400 dark:border-gray-600',
  success: 'border-emerald-400 dark:border-emerald-400',
  danger: 'border-rose-400 dark:border-rose-500',
};
