import React, { ReactNode } from 'react';

import AttackTypesBreakDownProps from './types/attack-types-break-down-props';

const RingDamage = ({
  break_down,
  type,
}: AttackTypesBreakDownProps): ReactNode => {
  console.log(break_down.regular);
  return <p>Ring Damage Here</p>;
};

export default RingDamage;
