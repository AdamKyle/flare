import {
  isLocationType,
  LOCATION_TYPE_LABELS,
} from '../../locations/enums/location-type';
import { LocationGemFormOptionLocationDefinition } from '../api/definitions/location-gem-form-options-definition';

export const buildLocationGemOptionLabel = (
  location: LocationGemFormOptionLocationDefinition
): string => {
  const mapName = location.map?.name ?? '';

  if (location.type !== null && isLocationType(location.type)) {
    const typeLabel = LOCATION_TYPE_LABELS[location.type];

    return `${location.name} [Special Type: ${typeLabel}] (${mapName})`;
  }

  return `${location.name} (${mapName})`;
};
