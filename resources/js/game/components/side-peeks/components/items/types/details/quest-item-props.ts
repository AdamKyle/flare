import BaseQuestItemDefinition from '../../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import LocationDetailsProps from '../../../../map-actions/location-details/types/location-details-props';

export default interface QuestItemProps {
  is_found_at_location?: boolean;
  location_props?: LocationDetailsProps;
  item: BaseQuestItemDefinition;
}
