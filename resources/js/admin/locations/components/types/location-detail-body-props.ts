import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';
import LocationDetailDefinition, {
  LocationDetailRelatedItemDefinition,
} from '../../api/definitions/location-detail-definition';
import UseLocationQuestItemsDefinition from '../../api/hooks/definitions/use-location-quest-items-definition';

export default interface LocationDetailBodyProps {
  location: LocationDetailDefinition;
  quest_items: UseLocationQuestItemsDefinition;
  on_open_related_item: (item: LocationDetailRelatedItemDefinition) => void;
  on_open_quest_item: (item: AdminQuestItemPresentationDefinition) => void;
  on_open_map?: (id: number) => void;
}
