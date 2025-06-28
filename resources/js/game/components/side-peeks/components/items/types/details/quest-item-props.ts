import ItemDetailsProps from './item-details-props';
import LocationDetailsProps from '../../../../map-actions/location-details/types/location-details-props';

export default interface QuestItemProps extends ItemDetailsProps {
  is_found_at_location?: boolean;
  location_props?: LocationDetailsProps;
}
