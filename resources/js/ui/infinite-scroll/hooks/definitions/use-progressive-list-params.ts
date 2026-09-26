export default interface UseProgressiveListParams {
  total_items: number;
  batch_size: number;
  initial_count?: number;
  reset_key?: string | number;
}
