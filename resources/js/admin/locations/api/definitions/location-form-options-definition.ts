import { LocationPin } from '../../enums/location-pin';
import { LocationType } from '../../enums/location-type';

export interface LocationFormOptionItem {
  value: number;
  label: string;
}

export default interface LocationFormOptionsDefinition {
  game_map: {
    id: number;
    name: string;
  };
  quest_items: LocationFormOptionItem[];
  location_types: LocationType[];
  special_pins: LocationPin[];
  coordinates: {
    x: number[];
    y: number[];
  };
}
