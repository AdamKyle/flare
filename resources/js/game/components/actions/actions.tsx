import React, { ReactNode, useState, useEffect } from 'react';

import { IconSection } from './partials/icon-section/icon-section';
import MonsterSection from './partials/monster-section/monster-section';
import { useManageMonsterStatSectionVisibility } from './partials/monster-stat-section/hooks/use-manage-monster-stat-section-visibility';
import { MonsterStatSection } from './partials/monster-stat-section/monster-stat-section';
import Card from '../../../ui/cards/card';

const Actions = (): ReactNode => {
  const { showMonsterStatsSection, showMonsterStats } =
    useManageMonsterStatSectionVisibility();
  const [scrollY, setScrollY] = useState(0);
  const [isMobile, setIsMobile] = useState(false);

  const handleScroll = () => setScrollY(window.scrollY);

  useEffect(() => {
    window.addEventListener('scroll', handleScroll);

    const checkMobile = () => setIsMobile(window.innerWidth < 768);

    checkMobile(); // Check on mount
    window.addEventListener('resize', checkMobile); // Check on resize

    return () => {
      window.removeEventListener('scroll', handleScroll);
      window.removeEventListener('resize', checkMobile);
    };
  }, []);

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
