import { Paginated } from '../api/definitions/batch-crafting-monitoring-definition';

export default function createEmptyBatchCraftingPage<T>(): Paginated<T> {
  return {
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
  };
}
