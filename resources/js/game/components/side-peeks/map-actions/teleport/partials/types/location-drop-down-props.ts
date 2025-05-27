import LocationsPositionDefinition from '../../../../../../api-definitions/map-details/locations-position-definition';
import { LocationTypes } from '../../enums/location-types';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface LocationDropDownProps {
  locations: LocationsPositionDefinition[];
  location_type_selected: LocationTypes | null;
  on_select: (item: DropdownItem, locationType: LocationTypes) => void;
  on_clear: () => void;
}
