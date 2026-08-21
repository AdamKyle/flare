import { BatchCraftingScreenNames } from '../../enums/batch-crafting-screen-names';

export default interface BatchCraftingEntryProps {
  on_ready: (screen: BatchCraftingScreenNames) => void;
}
