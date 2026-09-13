export enum GameMapType {
  BASE = 'base',
  MAP_GEM_WORLD = 'map_gem_world',
  LOCATION_GEM_WORLD = 'location_gem_world',
}

export const GAME_MAP_TYPE_LABELS: Record<GameMapType, string> = {
  [GameMapType.BASE]: 'Base Map',
  [GameMapType.MAP_GEM_WORLD]: 'World Gem',
  [GameMapType.LOCATION_GEM_WORLD]: 'Location Gem',
};

export const GAME_MAP_TYPE_VALUES: GameMapType[] = [
  GameMapType.BASE,
  GameMapType.MAP_GEM_WORLD,
  GameMapType.LOCATION_GEM_WORLD,
];

export const isGameMapType = (value: string | number): value is GameMapType => {
  if (typeof value !== 'string') {
    return false;
  }

  return GAME_MAP_TYPE_VALUES.some((typeValue) => typeValue === value);
};
