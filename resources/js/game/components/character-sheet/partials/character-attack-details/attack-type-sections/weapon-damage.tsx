import React, { ReactNode } from 'react';

import AttackTypesBreakDownProps from './types/attack-types-break-down-props';

const WeaponDamage = ({ break_down }: AttackTypesBreakDownProps): ReactNode => {
  console.log(break_down);

  return <p>Weapon Damage Here</p>;
};

export default WeaponDamage;
