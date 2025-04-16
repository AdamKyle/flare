import React, { ReactNode } from 'react';

import MonsterSection from '../monster-section/monster-section';
import { useScrollIconMenu } from './hooks/use-scroll-icon-menu';
import NavigationActionsComponent from './navigation-actions';
import ActionsProps from './types/actions-props';

import Card from 'ui/cards/card';

const Actions = (props: ActionsProps): ReactNode => {
  const { scrollY, isMobile } = useScrollIconMenu();

  return (
    <>
      <Card>
        <div className="w-full flex flex-col lg:flex-row justify-center relative">
          <div className="relative">
            <NavigationActionsComponent scrollY={scrollY} isMobile={isMobile} />
          </div>

          <div className="flex flex-col items-start w-full max-w-5xl mx-auto">
            <div className="w-full">
              <MonsterSection show_monster_stats={props.showMonsterStats} />
            </div>
          </div>

          <div className="hidden lg:block absolute top-[20px] left-1/2 translate-x-[360px] w-[480px]">
            <Card>content</Card>
          </div>
        </div>
      </Card>
    </>
  );
};

export default Actions;
