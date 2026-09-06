import LocationGemDetailDefinition from '../../api/definitions/location-gem-detail-definition';

export default interface LocationGemDetailBodyProps {
  location_gem: LocationGemDetailDefinition;
  on_activate_roll?: (gem_id: number) => void;
  activating_gem_id?: number | null;
}
