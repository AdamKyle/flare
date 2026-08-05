import { ChangeEvent, UIEvent } from 'react';

import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import CraftableItemDefinition from '../../api/definitions/craftable-item-definition';
import CraftingApiResponseDefinition from '../../api/definitions/crafting-api-response-definition';
import {
  ArmourTypeOptionDefinition,
  CraftTypeOptionDefinition,
} from '../../components/definitions/craft-type-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export interface CraftItemsFlowFilters {
  selectedType: string | null;
  armourType: string | null;
  selectedTypeOption: CraftTypeOptionDefinition | undefined;
  selectedArmourTypeOption: ArmourTypeOptionDefinition | undefined;
  handleTypeChange: (option: DropdownItem) => void;
  handleArmourTypeChange: (option: DropdownItem) => void;
  handleChangeType: () => void;
}

export interface CraftItemsFlowPicker {
  items: CraftableItemDefinition[];
  selectedItem: CraftableItemDefinition | null;
  searchInput: string;
  loading: boolean;
  isLoadingMore: boolean;
  canShowItems: boolean;
  handleSearch: (value: string) => void;
  handleSelectItem: (item: CraftableItemDefinition) => void;
  handleCraftItemsScroll: (event: UIEvent<HTMLDivElement>) => void;
}

export interface CraftItemsFlowTargets {
  craftForNpc: boolean;
  craftForEvent: boolean;
  canCraftForNpc: boolean;
  canCraftForEvent: boolean;
  handleCraftForNpcChange: (event: ChangeEvent<HTMLInputElement>) => void;
  handleCraftForEventChange: (event: ChangeEvent<HTMLInputElement>) => void;
}

export interface CraftItemsFlowResult {
  characterId: number;
  error: string | null;
  successMessage: string | null;
  craftedInventorySlotId: number | null;
  resultPreview: CraftingItemPreviewDefinition | null;
  isCrafting: boolean;
}

export interface CraftItemsFlowProgress {
  displayedCraftingData: CraftingApiResponseDefinition | null;
  isTimeoutActive: boolean;
  formattedRemaining: string;
  progress: number;
}

export interface CraftItemsFlowAction {
  isCraftingDisabled: boolean;
  inventoryIsFull: boolean;
  handleCraft: () => void;
}

export interface CraftItemsFlowNavigation {
  handleClose: () => void;
}

export default interface UseCraftItemsFlowDefinition {
  filters: CraftItemsFlowFilters;
  picker: CraftItemsFlowPicker;
  targets: CraftItemsFlowTargets;
  result: CraftItemsFlowResult;
  progress: CraftItemsFlowProgress;
  action: CraftItemsFlowAction;
  navigation: CraftItemsFlowNavigation;
}
