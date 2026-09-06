import MapGemDetailDefinition from '../../api/definitions/map-gem-detail-definition';

export default interface MapGemDetailBodyProps {
  map_gem: MapGemDetailDefinition;
  on_activate_roll?: (gem_id: number) => void;
  activating_gem_id?: number | null;
}
