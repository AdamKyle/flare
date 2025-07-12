import ItemDetails from '../../../api-definitions/items/item-details';

export default interface ShopCardProps {
  item: ItemDetails;
  view_item: (item_id: number) => void;
  compare_item: (item_id: number) => void;
}
