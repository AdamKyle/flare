export enum ItemAlchemyType {
  INCREASE_STATS = 'increase-stats',
  INCREASE_DAMAGE = 'increase-damage',
  INCREASE_ARMOUR = 'increase-armour',
  INCREASE_HEALING = 'increase-healing',
  INCREASE_SKILL_TYPE = 'increase-skill-type',
  DAMAGES_KINGDOMS = 'damages-kingdoms',
  HOLY_OILS = 'holy-oils',
  INCREASE_ALCHEMY_SKILL = 'increases-alchemy-skill',
}

export const ITEM_ALCHEMY_TYPE_LABELS: Record<ItemAlchemyType, string> = {
  [ItemAlchemyType.INCREASE_STATS]: 'Increase Stats',
  [ItemAlchemyType.INCREASE_DAMAGE]: 'Increase Damage',
  [ItemAlchemyType.INCREASE_ARMOUR]: 'Increase Armour',
  [ItemAlchemyType.INCREASE_HEALING]: 'Increase Healing',
  [ItemAlchemyType.INCREASE_SKILL_TYPE]: 'Increase Skill Type',
  [ItemAlchemyType.DAMAGES_KINGDOMS]: 'Damages Kingdoms',
  [ItemAlchemyType.HOLY_OILS]: 'Holy Oils',
  [ItemAlchemyType.INCREASE_ALCHEMY_SKILL]: 'Increases Alchemy Skill',
};

export const ITEM_ALCHEMY_TYPE_VALUES: ItemAlchemyType[] = [
  ItemAlchemyType.INCREASE_STATS,
  ItemAlchemyType.INCREASE_DAMAGE,
  ItemAlchemyType.INCREASE_ARMOUR,
  ItemAlchemyType.INCREASE_HEALING,
  ItemAlchemyType.INCREASE_SKILL_TYPE,
  ItemAlchemyType.DAMAGES_KINGDOMS,
  ItemAlchemyType.HOLY_OILS,
  ItemAlchemyType.INCREASE_ALCHEMY_SKILL,
];

export const isItemAlchemyType = (
  value: string | number
): value is ItemAlchemyType => {
  if (typeof value !== 'string') {
    return false;
  }

  return ITEM_ALCHEMY_TYPE_VALUES.some((alchemyType) => alchemyType === value);
};
