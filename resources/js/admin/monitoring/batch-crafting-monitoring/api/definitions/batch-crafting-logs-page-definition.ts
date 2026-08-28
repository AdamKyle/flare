import BatchCraftingLogEntry from './batch-crafting-log-entry-definition';

export default interface BatchCraftingLogsPage {
  data: BatchCraftingLogEntry[];
  current_page: number;
  last_page: number;
  total: number;
  next_cursor: string | null;
}
