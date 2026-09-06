import LocationGemFormDefinition from '../api/definitions/location-gem-form-definition';

export default interface LocationGemFormContentProps {
  location_gem_id: number | null;
  on_saved: (location_gem: LocationGemFormDefinition) => void;
  on_cancel: () => void;
  embedded: boolean;
}
