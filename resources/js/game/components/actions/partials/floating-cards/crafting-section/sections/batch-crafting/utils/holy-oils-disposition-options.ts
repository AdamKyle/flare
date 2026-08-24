import { dispositionLabel } from './batch-crafting-labels';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const HOLY_OILS_DISPOSITION_OPTIONS: DropdownItem[] = [
  BatchCraftingDisposition.KEEP,
  BatchCraftingDisposition.SELL,
  BatchCraftingDisposition.DESTROY,
  BatchCraftingDisposition.LIST,
  BatchCraftingDisposition.DISENCHANT,
].map((disposition) => ({
  label: dispositionLabel(disposition),
  value: disposition,
}));
