import { ItemSelectedType } from '../../types/item-selected-type';

export default interface UseManageMultipleSelectedItemsApiParams {
  character_id: number;
  apiParams: ItemSelectedType;
  url: string;
  onSuccess: () => void;
}
