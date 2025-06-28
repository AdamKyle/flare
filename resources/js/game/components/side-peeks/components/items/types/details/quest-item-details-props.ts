import ItemDetailsProps from './item-details-props';
import LocationDetailsProps from '../../../../map-actions/location-details/types/location-details-props';

export default interface QuestItemDetailsProps extends ItemDetailsProps {
  on_go_back: () => void;
  location_props?: LocationDetailsProps;
  is_found_at_location?: boolean;
}
