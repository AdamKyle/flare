import { LocationType } from '../../enums/location-type';

export default interface LocationListDefinition {
  id: number;
  name: string;
  map_name: string | null;
  type: LocationType | null;
  x: number;
  y: number;
}
