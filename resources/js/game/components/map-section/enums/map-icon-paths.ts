const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
const iconPath: string = `${basePath}/map-icons/`;

export const MapIconPaths = {
  CHARACTER: `${iconPath}/character.png`,
  PLAYER_KINGDOM: `${iconPath}/player-kingdom.png`,
  ENEMY_KINGDOM: `${iconPath}/enemy-kingdom.png`,
  NPC_KINGDOM: `${iconPath}/npc-kingdom.png`,
  LOCATION: `${iconPath}/location.png`,
  PORT_LOCATION: `${iconPath}/port-location.png`,
  CORRUPTED_LOCATION: `${iconPath}/corrupted-location.png`,
} as const;
