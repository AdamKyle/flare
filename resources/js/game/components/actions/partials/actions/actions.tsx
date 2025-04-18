import React, { ReactNode } from 'react';
import clsx from 'clsx';
import MonsterSection from '../monster-section/monster-section';
import AttackMessages from '../../components/fight-section/attack-messages';
import FightLog from '../monster-section/fight-log';
import { useScrollIconMenu } from './hooks/use-scroll-icon-menu';
import NavigationActionsComponent from './navigation-actions';
import ActionsProps from './types/actions-props';
import Card from 'ui/cards/card';
import AttackMessageDefinition from '../../components/fight-section/deffinitions/attack-message-definition';
import { AttackMessageType } from '../../components/fight-section/enums/attack-message-type';

interface ExtendedActionsProps extends ActionsProps {
  /** toggle showing the right‑hand Character/FightLog panel */
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
        {/* outer grid: 1col on mobile, 2col when no character panel, 3col when visible */}
        <div
          className={clsx(
            'grid grid-cols-1 gap-4 auto-rows-min p-4',
            {
              'lg:grid-cols-[6rem_1fr]': !showCharacterPanel,
              'lg:grid-cols-[6rem_1fr_1fr]': showCharacterPanel,
            }
          )}
        >
          {/* Sidebar */}
          <aside
            className={clsx(
              'flex justify-between lg:flex-col lg:space-y-2',
              'border-b lg:border-b-0 lg:border-r pb-2 lg:pb-0'
            )}
          >
            <NavigationActionsComponent scrollY={scrollY} isMobile={isMobile} />
          </aside>

          {/* Monster + Attack Messages */}
          <div className="flex flex-col items-center space-y-4">
            <MonsterSection show_monster_stats={showMonsterStats} />

            <div className="w-full p-2 bg-gray-100">
              <AttackMessages messages={messages} />
            </div>
          </div>

          {/* Character/FightLog panel, only if enabled */}
          <aside
            className={clsx(
              'p-4 bg-gray-50 border-t lg:border-t-0 lg:border-l',
              { hidden: !showCharacterPanel }
            )}
          >
            <FightLog close_action={() => {}} />
          </aside>
        </div>
      </Card>
    </div>
  );
};

export default Actions;
