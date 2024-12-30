import React, { ReactNode } from 'react';

import MonsterSection from '../monster-section/monster-section';
import { useScrollIconMenu } from './hooks/use-scroll-icon-menu';
import NavigationActionsComponent from './navigation-actions';
import ActionsProps from './types/actions-props';

import Card from 'ui/cards/card';

const Actions = (props: ActionsProps): ReactNode => {
  const { scrollY, isMobile } = useScrollIconMenu();

  return (
    <Card>
      <div className="w-full flex flex-col lg:flex-row">
        <div className="relative">
          <NavigationActionsComponent scrollY={scrollY} isMobile={isMobile} />
        </div>
        <div className="flex flex-col items-center lg:items-start w-full">
          <MonsterSection show_monster_stats={props.showMonsterStats} />
        </div>
      </div>
    </Card>
  );
};

export default Actions;
