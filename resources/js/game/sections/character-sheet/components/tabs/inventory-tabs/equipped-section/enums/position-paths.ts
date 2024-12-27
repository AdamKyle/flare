const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
const equipmentSection: string = `${basePath}/pixel-art/equipment-section`;

export enum Position {
    HELMET = "HELMET",
    SLEEVES_LEFT = "SLEEVES_LEFT",
    SLEEVES_RIGHT = "SLEEVES_RIGHT",
    GLOVES_LEFT = "GLOVES_LEFT",
    GLOVES_RIGHT = "GLOVES_RIGHT",
    LEGGINGS = "LEGGINGS",
    BODY = "BODY",
    FEET = "FEET",
    WEAPON_RIGHT = "WEAPON_RIGHT",
    WEAPON_LEFT = "WEAPON_LEFT",
    RING_RIGHT = "RING_RIGHT",
    RING_LEFT = "RING_LEFT",
    SPELL_RIGHT = "SPELL_RIGHT",
    SPELL_LEFT = "SPELL_LEFT",
    TRINKET = "TRINKET",
}

export const defaultPositionImage: Record<Position, string> = {
    [Position.HELMET]: `${equipmentSection}/head.png`,
    [Position.BODY]: `${equipmentSection}/body.png`,
    [Position.SLEEVES_LEFT]: `${equipmentSection}/left-arm.png`,
    [Position.SLEEVES_RIGHT]: `${equipmentSection}/right-arm.png`,
    [Position.LEGGINGS]: `${equipmentSection}/leggings.png`,
    [Position.FEET]: `${equipmentSection}/feet.png`,
    [Position.GLOVES_RIGHT]: `${equipmentSection}/right-hand.png`,
    [Position.GLOVES_LEFT]: `${equipmentSection}/left-hand.png`,
    [Position.WEAPON_LEFT]: `${equipmentSection}/nothing-equipped.png`,
    [Position.WEAPON_RIGHT]: `${equipmentSection}/nothing-equipped.png`,
    [Position.SPELL_LEFT]: `${equipmentSection}/nothing-equipped.png`,
    [Position.SPELL_RIGHT]: `${equipmentSection}/nothing-equipped.png`,
    [Position.RING_LEFT]: `${equipmentSection}/nothing-equipped.png`,
    [Position.RING_RIGHT]: `${equipmentSection}/nothing-equipped.png`,
    [Position.TRINKET]: `${equipmentSection}/nothing-equipped.png`,
};

export const defaultPositionImageAlt: Record<Position, string> = {
    [Position.HELMET]: "Helmet",
    [Position.BODY]: "Body",
    [Position.SLEEVES_LEFT]: "Left Sleeves",
    [Position.SLEEVES_RIGHT]: "Right Sleeves",
    [Position.GLOVES_LEFT]: "Left Gloves",
    [Position.GLOVES_RIGHT]: "Right Gloves",
    [Position.LEGGINGS]: "Leggings",
    [Position.FEET]: "Feet",
    [Position.WEAPON_RIGHT]: "Right Weapon",
    [Position.WEAPON_LEFT]: "Left Weapon",
    [Position.SPELL_RIGHT]: "Right Spell",
    [Position.SPELL_LEFT]: "Left Spell",
    [Position.RING_RIGHT]: "Right Ring",
    [Position.RING_LEFT]: "Left Ring",
    [Position.TRINKET]: "Trinket",
};

export const positionTypeMap: Record<Position, string> = {
    [Position.HELMET]: "helmet",
    [Position.BODY]: "body",
    [Position.SLEEVES_LEFT]: "sleeves",
    [Position.SLEEVES_RIGHT]: "sleeves",
    [Position.GLOVES_LEFT]: "gloves",
    [Position.GLOVES_RIGHT]: "gloves",
    [Position.LEGGINGS]: "leggings",
    [Position.FEET]: "feet",
    [Position.WEAPON_RIGHT]: "weapon",
    [Position.WEAPON_LEFT]: "weapon",
    [Position.SPELL_RIGHT]: "spell-one",
    [Position.SPELL_LEFT]: "spell-two",
    [Position.RING_RIGHT]: "ring-one",
    [Position.RING_LEFT]: "ring-two",
    [Position.TRINKET]: "trinket",
};
