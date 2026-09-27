export enum PassiveSkillEffect {
  KINGDOM_DEFENCE = 0,
  KINGDOM_RESOURCE_GAIN = 1,
  KINGDOM_UNIT_COST_REDUCTION = 2,
  KINGDOM_BUILDING_COST_REDUCTION = 3,
  UNLOCKS_BUILDING = 4,
  IRON_COST_REDUCTION = 5,
  POPULATION_COST_REDUCTION = 6,
  STEEL_SMELTING_TIME_REDUCTION = 7,
  AIRSHIP_ATTACK_INCREASE = 8,
  AIRSHIP_UNIT_DEFENCE = 9,
  RESOURCE_INCREASE = 10,
  STEEL_INCREASE = 11,
  CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION = 12,
  CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION = 13,
  RESOURCE_REQUEST_TIME_REDUCTION = 14,
  MASTER_FARMER = 15,
  MASTER_STONE_MASON = 16,
  MASTER_WOOD_WORKER = 17,
  MASTER_IRON_MINER = 18,
  MASTER_POTTER = 19,
  MASTER_STEEL_SMITH = 20,
}

const PASSIVE_SKILL_EFFECT_LABELS: Record<PassiveSkillEffect, string> = {
  [PassiveSkillEffect.KINGDOM_DEFENCE]: 'Kingdom Defence',
  [PassiveSkillEffect.KINGDOM_RESOURCE_GAIN]: 'Kingdom Resource Gain',
  [PassiveSkillEffect.KINGDOM_UNIT_COST_REDUCTION]:
    'Kingdom Unit Cost Reduction',
  [PassiveSkillEffect.KINGDOM_BUILDING_COST_REDUCTION]:
    'Kingdom Building Cost Reduction',
  [PassiveSkillEffect.UNLOCKS_BUILDING]: 'Unlocks New Building',
  [PassiveSkillEffect.IRON_COST_REDUCTION]: 'Iron Cost Reduction',
  [PassiveSkillEffect.POPULATION_COST_REDUCTION]: 'Population Cost Reduction',
  [PassiveSkillEffect.STEEL_SMELTING_TIME_REDUCTION]:
    'Steel Smelting Time Reduction',
  [PassiveSkillEffect.AIRSHIP_ATTACK_INCREASE]: 'Airship Attack Increase',
  [PassiveSkillEffect.AIRSHIP_UNIT_DEFENCE]: 'Airship Unit Defence',
  [PassiveSkillEffect.RESOURCE_INCREASE]: 'Resource Increase',
  [PassiveSkillEffect.STEEL_INCREASE]: 'Steel Increase',
  [PassiveSkillEffect.CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION]:
    'Capital City Building Request Travel Time Reduction',
  [PassiveSkillEffect.CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION]:
    'Capital City Unit Request Travel Time Reduction',
  [PassiveSkillEffect.RESOURCE_REQUEST_TIME_REDUCTION]:
    'Resource Request Time Reduction',
  [PassiveSkillEffect.MASTER_FARMER]: 'Master Farmer',
  [PassiveSkillEffect.MASTER_STONE_MASON]: 'Master Stone Mason',
  [PassiveSkillEffect.MASTER_WOOD_WORKER]: 'Master Wood Worker',
  [PassiveSkillEffect.MASTER_IRON_MINER]: 'Master Iron Miner',
  [PassiveSkillEffect.MASTER_POTTER]: 'Master Potter',
  [PassiveSkillEffect.MASTER_STEEL_SMITH]: 'Master Steel Smith',
};

export const isPassiveSkillEffect = (
  value: number | null
): value is PassiveSkillEffect =>
  value !== null && value in PASSIVE_SKILL_EFFECT_LABELS;

export const passiveSkillEffectLabel = (value: number): string => {
  if (!isPassiveSkillEffect(value)) {
    return `Unknown Effect (${value})`;
  }

  return PASSIVE_SKILL_EFFECT_LABELS[value];
};
