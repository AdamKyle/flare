const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
const defaultEquipment: string = `${basePath}/pixel-art/default-equipment`;

export enum Position {
  HELMET = 'HELMET',
  SLEEVES_LEFT = 'SLEEVES_LEFT',
  SLEEVES_RIGHT = 'SLEEVES_RIGHT',
  GLOVES_LEFT = 'GLOVES_LEFT',
  GLOVES_RIGHT = 'GLOVES_RIGHT',
  LEGGINGS = 'LEGGINGS',
  BODY = 'BODY',
  FEET = 'FEET',
  WEAPON_RIGHT = 'WEAPON_RIGHT',
  WEAPON_LEFT = 'WEAPON_LEFT',
  RING_RIGHT = 'RING_RIGHT',
  RING_LEFT = 'RING_LEFT',
  SPELL_RIGHT = 'SPELL_RIGHT',
  SPELL_LEFT = 'SPELL_LEFT',
  TRINKET = 'TRINKET',
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
  [Position.WEAPON_LEFT]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.WEAPON_RIGHT]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.SPELL_LEFT]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.SPELL_RIGHT]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.RING_LEFT]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.RING_RIGHT]: `${defaultEquipment}/nothing-equipped.png`,
  [Position.TRINKET]: `${defaultEquipment}/nothing-equipped.png`,
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
  [Position.WEAPON_RIGHT]: 'Right Weapon',
  [Position.WEAPON_LEFT]: 'Left Weapon',
  [Position.SPELL_RIGHT]: 'Right Spell',
  [Position.SPELL_LEFT]: 'Left Spell',
  [Position.RING_RIGHT]: 'Right Ring',
  [Position.RING_LEFT]: 'Left Ring',
  [Position.TRINKET]: 'Trinket',
};

export const positionTypeMap: Record<Position, string> = {
  [Position.HELMET]: 'helmet',
  [Position.BODY]: 'body',
  [Position.SLEEVES_LEFT]: 'sleeves',
  [Position.SLEEVES_RIGHT]: 'sleeves',
  [Position.GLOVES_LEFT]: 'gloves',
  [Position.GLOVES_RIGHT]: 'gloves',
  [Position.LEGGINGS]: 'leggings',
  [Position.FEET]: 'feet',
  [Position.WEAPON_RIGHT]: 'weapon',
  [Position.WEAPON_LEFT]: 'weapon',
  [Position.SPELL_RIGHT]: 'spell-one',
  [Position.SPELL_LEFT]: 'spell-two',
  [Position.RING_RIGHT]: 'ring-one',
  [Position.RING_LEFT]: 'ring-two',
  [Position.TRINKET]: 'trinket',
};
