import clsx from 'clsx';
import React, { useState } from 'react';

import MonsterSection from './monster-section';
import MonsterActionsProps from './types/monster-actions-props';
import AttackMessages from '../../components/fight-section/attack-messages';
import AttackMessageDefinition from '../../components/fight-section/deffinitions/attack-message-definition';
import { AttackMessageType } from '../../components/fight-section/enums/attack-message-type';

const MonsterActions = ({
  is_showing_side_section,
  show_monster_section,
}: MonsterActionsProps) => {
  const [hasInitiatedFight, setHasInitiatedFight] = useState(false);

  const messages: AttackMessageDefinition[] = [
    {
      message: 'You Attack for 150,000 Damage!',
      type: AttackMessageType.PLAYER_ATTACK,
    },
    {
      message: 'Your spells charge and magic crackles in the air!',
      type: AttackMessageType.REGULAR,
    },
    {
      message: 'The enemy stops your spells and attack you for 125,000 Damage',
      type: AttackMessageType.ENEMY_ATTACK,
    },
  ];

  const renderAttackMessages = () => {
    if (!hasInitiatedFight) {
      return null;
    }

    return (
      <div className="mt-4 rounded-lg bg-gray-100 dark:bg-gray-700 p-4 text-sm border border-solid border-gray-200 dark:border-gray-800 ">
        <AttackMessages messages={messages} />
      </div>
    );
  };

  return (
    <div className="flex flex-col items-center space-y-4">
      <div
        className={clsx('w-full lg:w-1/2 mx-auto', {
          'lg:w-full': is_showing_side_section,
        })}
      >
        <MonsterSection
          show_monster_stats={show_monster_section}
          has_initiate_monster_fight={setHasInitiatedFight}
        />
        {renderAttackMessages()}
      </div>
    </div>
  );
};

export default MonsterActions;
