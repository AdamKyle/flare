const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
const iconPath: string = `${basePath}/map-icons/`;

export const MapIconPaths = {
  CHARACTER: `${iconPath}/character.png`,
  PLAYER_KINGDOM: `${iconPath}/player-kingdom-icon-16x16.png`,
  ENEMY_KINGDOM: `${iconPath}/player-kingdom-icon.png`,
} as const;
