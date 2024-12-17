import React, { ReactNode } from 'react';

import { IconSection } from '../icon-section/icon-section';
import MonsterSection from '../monster-section/monster-section';
import { useManageMonsterStatSectionVisibility } from '../monster-stat-section/hooks/use-manage-monster-stat-section-visibility';
import { MonsterStatSection } from '../monster-stat-section/monster-stat-section';
import { useScrollIconMenu } from './hooks/use-scroll-icon-menu';

import Card from 'ui/cards/card';

const Actions = (): ReactNode => {
  const { showMonsterStatsSection, showMonsterStats } =
    useManageMonsterStatSectionVisibility();

  const { scrollY, isMobile } = useScrollIconMenu();

  return (
    <Card>
      <div className="w-full flex flex-col lg:flex-row">
        <div className="relative">
          {isMobile ? (
            <div>
              <IconSection />
            </div>
          ) : (
            <div
              style={{
                position: 'absolute',
                top: `${scrollY + 10}px`,
                left: '10px',
                transition: 'top 0.2s',
              }}
            >
              <IconSection />
            </div>
          )}
        </div>
        <div className="flex flex-col items-center lg:items-start w-full">
          {showMonsterStatsSection ? (
            <MonsterStatSection />
          ) : (
            <MonsterSection show_monster_stats={showMonsterStats} />
          )}
        </div>
      </div>
    </Card>
  );
};

export default Actions;
