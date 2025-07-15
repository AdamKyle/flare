import ItemDetails from '../../../../api-definitions/items/item-details';

export default interface ItemDetailsProps {
  item: ItemDetails;
  show_advanced_button?: boolean;
  show_in_between_separator?: boolean;
  damage_ac_on_top?: boolean;
}
