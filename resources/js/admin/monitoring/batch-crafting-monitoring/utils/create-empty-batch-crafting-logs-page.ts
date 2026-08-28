import BatchCraftingLogsPage from '../api/definitions/batch-crafting-logs-page-definition';

export default function createEmptyBatchCraftingLogsPage(): BatchCraftingLogsPage {
  return {
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
    next_cursor: null,
  };
}
