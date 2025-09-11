export default interface UseManageMultipleSelectedItemsApiParams {
  character_id: number;
  apiParams: ItemSelectedType;
  url: string;
  onSuccess: () => void;
}
