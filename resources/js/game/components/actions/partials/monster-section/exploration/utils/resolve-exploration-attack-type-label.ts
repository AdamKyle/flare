import { ExplorationAttackType } from '../enums/exploration-attack-type';

const EXPLORATION_ATTACK_TYPE_LABELS: Record<string, string> = {
  [ExplorationAttackType.ATTACK]: 'Attack',
  [ExplorationAttackType.CAST]: 'Cast',
  [ExplorationAttackType.CAST_AND_ATTACK]: 'Cast and Attack',
  [ExplorationAttackType.ATTACK_AND_CAST]: 'Attack and Cast',
  [ExplorationAttackType.DEFEND]: 'Defend',
};

export const resolveExplorationAttackTypeLabel = (attackType: string): string =>
  EXPLORATION_ATTACK_TYPE_LABELS[attackType] ?? attackType;
