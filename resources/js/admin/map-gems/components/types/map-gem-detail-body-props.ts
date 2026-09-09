import MapGemDetailDefinition from '../../api/definitions/map-gem-detail-definition';

export interface MapGemDetailBodyNavigationDefinition {
  on_open_map?: (id: number) => void;
}

export default interface MapGemDetailBodyProps {
  map_gem: MapGemDetailDefinition;
  is_side_peek?: boolean;
  navigation?: MapGemDetailBodyNavigationDefinition;
}
