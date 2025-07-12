import ItemDetails from '../../../api-definitions/items/item-details';

export default interface SimpleItemDetailsProps {
  item: ItemDetails;
  show_advanced_button?: boolean;
  on_close: () => void;
}
