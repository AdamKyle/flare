import MapGemFormDefinition from '../api/definitions/map-gem-form-definition';

export default interface MapGemFormContentProps {
  map_gem_id: number | null;
  on_saved: (map_gem: MapGemFormDefinition) => void;
  on_cancel: () => void;
  embedded: boolean;
}
