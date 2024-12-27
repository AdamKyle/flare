import { EquipmentTypes } from "./equipment-types";
import { defaultPositionImage, Position } from "./position-paths";

const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
const equipmentSection: string = `${basePath}/pixel-art/equipped-sections`;

export const positionEquipmentPaths: Record<
    Position,
    Record<EquipmentTypes, string>
> = {
    [Position.HELMET]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/helmet/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/helmet/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/helmet/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/helmet/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/helmet/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/helmet/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/helmet/cosmic.png`,
    },
    [Position.BODY]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/body/body-armour-normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/body/body-armour-one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/body/body-armour-two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/body/body-armour-holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/body/body-armour-unique.gif`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/body/body-armour-mythical.gif`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/body/body-armour-cosmic.gif`,
    },
    [Position.SLEEVES_LEFT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/sleeves_left/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/sleeves_left/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/sleeves_left/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/sleeves_left/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/sleeves_left/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/sleeves_left/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/sleeves_left/cosmic.png`,
    },
    [Position.SLEEVES_RIGHT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/sleeves_right/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/sleeves_right/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/sleeves_right/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/sleeves_right/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/sleeves_right/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/sleeves_right/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/sleeves_right/cosmic.png`,
    },
    [Position.LEGGINGS]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/leggings/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/leggings/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/leggings/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/leggings/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/leggings/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/leggings/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/leggings/cosmic.png`,
    },
    [Position.FEET]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/feet/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/feet/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/feet/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/feet/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/feet/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/feet/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/feet/cosmic.png`,
    },
    [Position.GLOVES_RIGHT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/gloves_right/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/gloves_right/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/gloves_right/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/gloves_right/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/gloves_right/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/gloves_right/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/gloves_right/cosmic.png`,
    },
    [Position.GLOVES_LEFT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/gloves_left/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/gloves_left/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/gloves_left/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/gloves_left/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/gloves_left/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/gloves_left/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/gloves_left/cosmic.png`,
    },
    [Position.WEAPON_LEFT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/weapon_left/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/weapon_left/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/weapon_left/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/weapon_left/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/weapon_left/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/weapon_left/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/weapon_left/cosmic.png`,
    },
    [Position.WEAPON_RIGHT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/weapon_right/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/weapon_right/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/weapon_right/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/weapon_right/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/weapon_right/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/weapon_right/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/weapon_right/cosmic.png`,
    },
    [Position.SPELL_LEFT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/spell_left/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/spell_left/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/spell_left/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/spell_left/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/spell_left/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/spell_left/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/spell_left/cosmic.png`,
    },
    [Position.SPELL_RIGHT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/spell_right/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/spell_right/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/spell_right/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/spell_right/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/spell_right/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/spell_right/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/spell_right/cosmic.png`,
    },
    [Position.RING_LEFT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/ring_left/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/ring_left/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/ring_left/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/ring_left/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/ring_left/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/ring_left/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/ring_left/cosmic.png`,
    },
    [Position.RING_RIGHT]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/ring_right/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/ring_right/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/ring_right/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/ring_right/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/ring_right/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/ring_right/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/ring_right/cosmic.png`,
    },
    [Position.TRINKET]: {
        [EquipmentTypes.NORMAL]: `${equipmentSection}/trinket/normal.png`,
        [EquipmentTypes.ONE_ENCHANT]: `${equipmentSection}/trinket/one-enchant.png`,
        [EquipmentTypes.TWO_ENCHANTS]: `${equipmentSection}/trinket/two-enchants.png`,
        [EquipmentTypes.HOLY]: `${equipmentSection}/trinket/holy.png`,
        [EquipmentTypes.UNIQUE]: `${equipmentSection}/trinket/unique.png`,
        [EquipmentTypes.MYTHICAL]: `${equipmentSection}/trinket/mythical.png`,
        [EquipmentTypes.COSMIC]: `${equipmentSection}/trinket/cosmic.png`,
    },
};

export const getEquipmentImagePath = (
    position: Position,
    equipmentType: EquipmentTypes,
): string => {
    const equipmentPaths = positionEquipmentPaths[position];

    if (equipmentPaths && equipmentPaths[equipmentType]) {
        return equipmentPaths[equipmentType];
    }

    return defaultPositionImage[position];
};
