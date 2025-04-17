import React from 'react';

import FightLogProps from './types/fight-log-props';
import AttackMessages from '../../components/fight-section/attack-messages';
import AttackMessageDefinition from '../../components/fight-section/deffinitions/attack-message-definition';
import { AttackMessageType } from '../../components/fight-section/enums/attack-message-type';

const FightLog = ({ close_action }: FightLogProps) => {
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

  return (
    <div className="shadow-lg rounded-sm border border-gray-500 dark:border-gray-700 w-[20rem] md:w-[28rem] z-20 max-w-none text-black dark:text-gray-300">
      <div className="bg-gray-400 dark:bg-gray-700 border-b-2 border-b-gray-500 dark:border-b-gray-600 px-4 py-3 flex items-center justify-between">
        <h3 className="text-lg font-semibold mb-0">Attack Log</h3>
        <button
          className="p-0 bg-transparent border-none cursor-pointer transition-all duration-300 ease-in-out transform hover:scale-105"
          onClick={close_action}
          aria-label="Close"
        >
          <i
            className="fas fa-times-circle text-rose-600 dark:text-rose-500 rounded-full text-lg p-1"
            aria-hidden="true"
          ></i>
        </button>
      </div>
      <div className="p-4 bg-gray-200 dark:bg-gray-500">
        <AttackMessages messages={messages} />
      </div>
    </div>
  );
};

export default FightLog;
