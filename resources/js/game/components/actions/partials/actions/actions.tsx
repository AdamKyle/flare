import React, { ReactNode } from 'react';
import MonsterSection from '../monster-section/monster-section';
import { useScrollIconMenu } from './hooks/use-scroll-icon-menu';
import NavigationActionsComponent from './navigation-actions';
import ActionsProps from './types/actions-props';
import Card from 'ui/cards/card';
import FightLog from '../monster-section/fight-log';

const Actions = (props: ActionsProps): ReactNode => {
  const { scrollY, isMobile } = useScrollIconMenu();

  return (
    <Card>
      <div className="
        grid
        grid-cols-1
        lg:grid-cols-[auto_minmax(0,64rem)]
        xl:grid-cols-[auto_minmax(0,64rem)_1fr]
        justify-items-center
        gap-x-8
      ">
        <div className="justify-self-center lg:justify-self-start">
          <NavigationActionsComponent scrollY={scrollY} isMobile={isMobile} />
        </div>
        <div className="justify-self-center w-full max-w-5xl">
          <div className='xl:ml-[450px]'>
          <MonsterSection show_monster_stats={props.showMonsterStats} />
          </div>
        </div>
        <div className="hidden xl:block col-start-3 justify-self-center w-[480px]">
          <FightLog close_action={() => {}} />
        </div>
      </div>
    </Card>
  );
};

export default Actions;
