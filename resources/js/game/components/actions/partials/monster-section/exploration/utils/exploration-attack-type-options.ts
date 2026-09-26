import { resolveExplorationAttackTypeLabel } from './resolve-exploration-attack-type-label';
import { ExplorationAttackType } from '../enums/exploration-attack-type';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const explorationAttackTypeOptions: DropdownItem[] = Object.values(
  ExplorationAttackType
).map((attackType) => ({
  label: resolveExplorationAttackTypeLabel(attackType),
  value: attackType,
}));
