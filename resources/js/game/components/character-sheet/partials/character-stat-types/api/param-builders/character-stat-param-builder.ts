import { match } from 'ts-pattern';

import { CharacterStatParamBuilderDefinition } from './definitions/character-stat-param-builder-definition';
import { StatTypes } from '../../../../enums/stat-types';

export const characterStatParamBuilder = (
  stat: StatTypes
): CharacterStatParamBuilderDefinition => {
  return match(stat)
    .with(StatTypes.STR, () => ({ stat_type: 'str' }))
    .with(StatTypes.DEX, () => ({ stat_type: 'dex' }))
    .with(StatTypes.INT, () => ({ stat_type: 'int' }))
    .with(StatTypes.CHR, () => ({ stat_type: 'chr' }))
    .with(StatTypes.AGI, () => ({ stat_type: 'agi' }))
    .with(StatTypes.DUR, () => ({ stat_type: 'dur' }))
    .with(StatTypes.FOCUS, () => ({ stat_type: 'focus' }))
    .otherwise(() => undefined);
};
