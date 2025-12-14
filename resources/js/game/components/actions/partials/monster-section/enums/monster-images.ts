const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;

export const MonsterImages = {
  SEWER_RAT: `${basePath}/monster-images/surface-monster-images/sewer-rat.png`,
  WHITE_DRAGON: `${basePath}/monster-images/surface-monster-images/great-white-dragon.png`,
  LABYRINTH_FIEND: `${basePath}/monster-images/surface-monster-images/labyrinth-fiend.png`,
  LADY_OF_THE_GRAVE: `${basePath}/monster-images/surface-monster-images/lady-of-the-grave.png`,
  DERANGED_CLERIC: `${basePath}/monster-images/surface-monster-images/deranged-cleric.png`,
  DEATHS_MINION: `${basePath}/monster-images/surface-monster-images/deaths-minion.png`,
} as const;

const MonsterImageProgression: readonly string[] = [
  MonsterImages.SEWER_RAT,
  MonsterImages.WHITE_DRAGON,
  MonsterImages.LABYRINTH_FIEND,
  MonsterImages.LADY_OF_THE_GRAVE,
  MonsterImages.DERANGED_CLERIC,
  MonsterImages.DEATHS_MINION,
] as const;

export default MonsterImageProgression;
