import ItemDetails from '../../../api-definitions/items/item-details';

export default interface SimpleItemDetailsProps {
  item: ItemDetails;
  show_advanced_button?: boolean;
  show_shop_actions?: boolean;
  on_close: () => void;
}
