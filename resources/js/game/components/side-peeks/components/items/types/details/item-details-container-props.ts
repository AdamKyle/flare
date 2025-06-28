import ItemDetailsProps from './item-details-props';
import LocationDetailsProps from '../../../../map-actions/location-details/types/location-details-props';
import { ItemTypeToView } from '../../enums/item-type-to-view';

export default interface ItemDetailsContainerProps extends ItemDetailsProps {
  location_props?: LocationDetailsProps;
  is_found_at_location?: boolean;
  item_type_to_view: ItemTypeToView;
  on_close: () => void;
}
