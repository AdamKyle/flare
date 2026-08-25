import BaseMapApiDefinition from '../hooks/definitions/base-map-api-definition';

const isRecord = (value: unknown): value is Record<string, unknown> => {
  return typeof value === 'object' && value !== null;
};

const isNonEmptyStringArray = (value: unknown): value is string[] => {
  return (
    Array.isArray(value) &&
    value.length > 0 &&
    value.every((entry) => typeof entry === 'string')
  );
};

const hasUsableTileGrid = (tiles: unknown): tiles is string[][] => {
  return (
    Array.isArray(tiles) &&
    tiles.length > 0 &&
    tiles.every((row) => isNonEmptyStringArray(row))
  );
};

const isValidCharacterPosition = (
  characterPosition: unknown
): characterPosition is BaseMapApiDefinition['character_position'] => {
  if (!isRecord(characterPosition)) {
    return false;
  }

  return (
    typeof characterPosition.x_position === 'number' &&
    typeof characterPosition.y_position === 'number'
  );
};

const isValidTimeOutDetails = (
  timeOutDetails: unknown
): timeOutDetails is BaseMapApiDefinition['time_out_details'] => {
  if (!isRecord(timeOutDetails)) {
    return false;
  }

  return (
    typeof timeOutDetails.can_move === 'boolean' &&
    typeof timeOutDetails.time_left === 'number' &&
    typeof timeOutDetails.show_timer === 'boolean'
  );
};

const isValidBaseMapApiResponse = (
  response: unknown
): response is BaseMapApiDefinition => {
  if (!isRecord(response)) {
    return false;
  }

  return (
    hasUsableTileGrid(response.tiles) &&
    Array.isArray(response.character_kingdoms) &&
    Array.isArray(response.npc_kingdoms) &&
    Array.isArray(response.enemy_kingdoms) &&
    Array.isArray(response.locations) &&
    isValidCharacterPosition(response.character_position) &&
    isValidTimeOutDetails(response.time_out_details) &&
    typeof response.has_conjurable_celestials === 'boolean'
  );
};

export default isValidBaseMapApiResponse;
