import LocationGemDetailDefinition from '../../api/definitions/location-gem-detail-definition';

export interface LocationGemDetailBodyNavigationDefinition {
  on_open_map?: (id: number) => void;
  on_open_location?: (id: number) => void;
}

export default interface LocationGemDetailBodyProps {
  location_gem: LocationGemDetailDefinition;
  is_side_peek?: boolean;
  navigation?: LocationGemDetailBodyNavigationDefinition;
}
