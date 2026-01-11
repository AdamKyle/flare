const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
const defaultEquipment: string = `${basePath}/pixel-art/default-equipment-items`;
const normalItems: string = `${basePath}/pixel-art/normal-items`;
const oneEnchantItems: string = `${basePath}/pixel-art/one-enchant-items`;
const twoEnchantItems: string = `${basePath}/pixel-art/two-enchants-items`;
const holyItems: string = `${basePath}/pixel-art/holy-items`;
const uniqueItems: string = `${basePath}/pixel-art/unique-items`;
const mythicalItems: string = `${basePath}/pixel-art/mythical-items`;
const cosmicItems: string = `${basePath}/pixel-art/cosmic-items`;

export const EquipmentImagePaths = {
  NORMAL_ITEMS: normalItems,
  ONE_ENCHANT_ITEMS: oneEnchantItems,
  TWO_ENCHANT_ITEMS: twoEnchantItems,
  HOLY_ITEMS: holyItems,
  UNIQUE_ITEMS: uniqueItems,
  MYTHICAL_ITEMS: mythicalItems,
  COSMIC_ITEMS: cosmicItems,
};

export enum Position {
  HELMET = 'HELMET',
  SLEEVES_LEFT = 'SLEEVES_LEFT',
  SLEEVES_RIGHT = 'SLEEVES_RIGHT',
  GLOVES_LEFT = 'GLOVES_LEFT',
  GLOVES_RIGHT = 'GLOVES_RIGHT',
  LEGGINGS = 'LEGGINGS',
  BODY = 'BODY',
  FEET = 'FEET',
  RING_HAND = 'RING_HAND',
  LEFT_HAND = 'LEFT_HAND',
  RING_ONE = 'RING_ONE',
  RING_TWO = 'RING_TWO',
  SPELL_ONE = 'SPELL_ONE',
  SPELL_TWO = 'SPELL_TWO',
  TRINKET = 'TRINKET',
  ARTIFACT = 'ARTIFACT',
}

export enum InventoryPositionDefinition {
  LEFT_HAND = 'left-hand',
  RIGHT_HAND = 'right-hand',
  BODY = 'body',
  SHIELD = 'shield',
  LEGGINGS = 'leggings',
  FEET = 'feet',
  SLEEVES = 'sleeves',
  HELMET = 'helmet',
  GLOVES = 'gloves',
  RING_ONE = 'ring-one',
  RING_TWO = 'ring-two',
  SPELL_ONE = 'spell-one',
  SPELL_TWO = 'spell-two',
  TRINKET = 'trinket',
  ARTIFACT = 'artifact',
}

export const defaultPositionImage: Record<Position, string> = {
  [Position.HELMET]: `${defaultEquipment}/head.png`,
  [Position.BODY]: `${defaultEquipment}/body.png`,
  [Position.SLEEVES_LEFT]: `${defaultEquipment}/left-arm.png`,
  [Position.SLEEVES_RIGHT]: `${defaultEquipment}/right-arm.png`,
  [Position.LEGGINGS]: `${defaultEquipment}/leggings.png`,
  [Position.FEET]: `${defaultEquipment}/feet.png`,
  [Position.GLOVES_RIGHT]: `${defaultEquipment}/right-hand.png`,
  [Position.GLOVES_LEFT]: `${defaultEquipment}/left-hand.png`,
  [Position.LEFT_HAND]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.RING_HAND]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.SPELL_TWO]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.SPELL_ONE]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.RING_TWO]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.RING_ONE]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.TRINKET]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.ARTIFACT]: `${defaultEquipment}/nothing-equipped.png`,
};

export const defaultPositionImageAlt: Record<Position, string> = {
  [Position.HELMET]: 'Helmet',
  [Position.BODY]: 'Body',
  [Position.SLEEVES_LEFT]: 'Left Sleeves',
  [Position.SLEEVES_RIGHT]: 'Right Sleeves',
  [Position.GLOVES_LEFT]: 'Left Gloves',
  [Position.GLOVES_RIGHT]: 'Right Gloves',
  [Position.LEGGINGS]: 'Leggings',
  [Position.FEET]: 'Feet',
  [Position.RING_HAND]: 'Right Hand Weapon',
  [Position.LEFT_HAND]: 'Left Hand Weapon',
  [Position.SPELL_ONE]: 'Right Spell Slot',
  [Position.SPELL_TWO]: 'Left Spell SLot',
  [Position.RING_ONE]: 'Right Ring Slot',
  [Position.RING_TWO]: 'Left Ring Slot',
  [Position.TRINKET]: 'Trinket',
  [Position.ARTIFACT]: 'Artifact',
};
