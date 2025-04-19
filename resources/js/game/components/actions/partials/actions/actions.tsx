import clsx from 'clsx';
import React, { ReactNode } from 'react';

import NavigationActionsComponent from './navigation-actions';
import AttackMessages from '../../components/fight-section/attack-messages';
import FightLog from '../monster-section/fight-log';
import MonsterSection from '../monster-section/monster-section';
import { useScrollIconMenu } from './hooks/use-scroll-icon-menu';
import ActionsProps from './types/actions-props';
import AttackMessageDefinition from '../../components/fight-section/deffinitions/attack-message-definition';
import { AttackMessageType } from '../../components/fight-section/enums/attack-message-type';

import Card from 'ui/cards/card';

interface ExtendedActionsProps extends ActionsProps {
  showCharacterPanel: boolean;
}

const Actions = (props: ExtendedActionsProps): ReactNode => {
  const { scrollY, isMobile } = useScrollIconMenu();
  const { showCharacterPanel, showMonsterStats } = props;

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
    <div className="w-3/4 mx-auto">
      <Card>
        <div
          className={clsx('grid grid-cols-1 gap-4 p-4 items-start', {
            'lg:grid-cols-[6rem_1fr]': !showCharacterPanel,
            'lg:grid-cols-[6rem_1fr_1fr]': showCharacterPanel,
          })}
        >
          {/* Sidebar */}
          <aside className="flex justify-between lg:flex-col lg:space-y-2 border-b lg:border-b-0 lg:border-r pb-2 lg:pb-0">
            <NavigationActionsComponent scrollY={scrollY} isMobile={isMobile} />
          </aside>

          {/* Monster + Attack Messages */}
          <div className="flex flex-col items-center space-y-4">
            <MonsterSection show_monster_stats={showMonsterStats} />
            <div className="w-full p-2 bg-gray-100">
              <AttackMessages messages={messages} />
            </div>
          </div>

          {/* Character/FightLog panel (horizontal center only) */}
          {showCharacterPanel && (
            <aside className="p-4 bg-gray-50 border-t lg:border-t-0 lg:border-l flex justify-center">
              <FightLog close_action={() => {}} />
            </aside>
          )}
        </div>
      </Card>
    </div>
  );
};

export default Actions;
