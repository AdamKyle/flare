import clsx from 'clsx';
import React from 'react';

import MonsterSection from './monster-section';
import MonsterActionsProps from './types/monster-actions-props';

const MonsterActions = ({
  is_showing_side_section,
  show_monster_section,
}: MonsterActionsProps) => {
  return (
    <div className="flex flex-col items-center space-y-4">
      <div
        className={clsx('mx-auto w-full lg:w-1/2', {
          'lg:w-full': is_showing_side_section,
        })}
      >
        <MonsterSection show_monster_stats={show_monster_section} />
      </div>
    </div>
  );
};

export default MonsterActions;
