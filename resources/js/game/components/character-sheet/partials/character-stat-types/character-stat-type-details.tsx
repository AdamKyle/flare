import React, { ReactNode } from 'react';

import CharacterStatTypeDetailsProps from './types/character-stat-type-details-props';
import { getStatName } from '../../enums/stat-types';

export const CharacterStatTypeDetails = ({
  stat_type,
}: CharacterStatTypeDetailsProps): ReactNode => {
  return <p>Stat Details for {getStatName(stat_type)}</p>;
};
