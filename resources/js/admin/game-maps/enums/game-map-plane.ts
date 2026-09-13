export enum GameMapPlane {
  SURFACE = 'Surface',
  LABYRINTH = 'Labyrinth',
  DUNGEONS = 'Dungeons',
  SHADOW_PLANE = 'Shadow Plane',
  HELL = 'Hell',
  PURGATORY = 'Purgatory',
  TWISTED_MEMORIES = 'Twisted Memories',
  ICE_PLANE = 'The Ice Plane',
  DELUSIONAL_MEMORIES = 'Delusional Memories',
}

export const GAME_MAP_PLANE_VALUES: GameMapPlane[] = [
  GameMapPlane.SURFACE,
  GameMapPlane.LABYRINTH,
  GameMapPlane.DUNGEONS,
  GameMapPlane.SHADOW_PLANE,
  GameMapPlane.HELL,
  GameMapPlane.PURGATORY,
  GameMapPlane.TWISTED_MEMORIES,
  GameMapPlane.ICE_PLANE,
  GameMapPlane.DELUSIONAL_MEMORIES,
];

export const isGameMapPlane = (
  value: string | number
): value is GameMapPlane => {
  if (typeof value !== 'string') {
    return false;
  }

  return GAME_MAP_PLANE_VALUES.some((planeValue) => planeValue === value);
};
