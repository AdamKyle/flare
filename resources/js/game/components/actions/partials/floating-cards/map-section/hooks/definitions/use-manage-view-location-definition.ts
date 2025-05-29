import LocationInfo from '../types/location-info';

export default interface UseManageViewLocationDefinition {
  isViewLocationEnabled: boolean;
  locationData: LocationInfo | null;
  canViewLocationData: (
    isEnabled: boolean,
    locationInfo: LocationInfo | null
  ) => void;
}
