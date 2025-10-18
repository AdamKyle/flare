const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;

export const MonsterImages = {
  GOBLIN: `${basePath}/monster-images/surface-monster-images/goblin.png`,
  WHITE_DRAGON: `${basePath}/monster-images/surface-monster-images/white-dragon.png`,
  WAILING_CHILD: `${basePath}/monster-images/surface-monster-images/wailing-child.png`,
  ANGRY_PEASANT: `${basePath}/monster-images/surface-monster-images/angry-peasant.png`,
  SOULESS_ARCHER: `${basePath}/monster-images/surface-monster-images/souless-archer.png`,
  GOD_OF_LIZARD_KIN: `${basePath}/monster-images/surface-monster-images/god-of-the-lizard-kin.png`,
} as const;

const MonsterImageProgression: readonly string[] = [
  MonsterImages.GOBLIN,
  MonsterImages.WHITE_DRAGON,
  MonsterImages.WAILING_CHILD,
  MonsterImages.ANGRY_PEASANT,
  MonsterImages.SOULESS_ARCHER,
  MonsterImages.GOD_OF_LIZARD_KIN,
] as const;

export default MonsterImageProgression;
