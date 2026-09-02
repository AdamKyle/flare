import { LocationType } from '../../enums/location-type';

export default interface LocationListFiltersDefinition {
  game_map_id: number | null;
  type: LocationType | null;
  [key: string]: number | LocationType | null;
}
