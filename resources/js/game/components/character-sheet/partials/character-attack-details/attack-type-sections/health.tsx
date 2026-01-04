import React, { ReactNode } from 'react';

import AttackTypesBreakDownProps from './types/attack-types-break-down-props';

const Health = ({ break_down, type }: AttackTypesBreakDownProps): ReactNode => {
  console.log(break_down);
  return <p>Health Amount Here</p>;
};

export default Health;
