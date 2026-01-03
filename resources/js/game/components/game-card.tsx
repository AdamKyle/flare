import { setScreenIntent } from 'configuration/screen-manager/screen-intent';
import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import React, { ReactNode, useState } from 'react';

import Actions from './actions/partials/actions/actions';
import { useManageMarketVisibility } from './actions/partials/floating-cards/map-section/hooks/use-manage-market-visibility';
import { useManagePlayerKingdomManagementVisibility } from './actions/partials/floating-cards/map-section/hooks/use-manage-player-kingdom-management-visibility';
import { useManageMonsterStatSectionVisibility } from './actions/partials/monster-stat-section/hooks/use-manage-monster-stat-section-visibility';
import { MonsterStatSection } from './actions/partials/monster-stat-section/monster-stat-section';
import FullMap from './map-section/full-map';
import { useToggleFullMapVisibility } from './map-section/hooks/use-toggle-full-map-visibility';
import Market from './market/market';
import PlayerKingdoms from './player-kingdoms/player-kingdoms';

export const GameCard = (): ReactNode => {
  const [monsterIdToView, setMonsterIdToView] = useState<number>(0);

  const { showMonsterStatsSection, showMonsterStats, closeMonsterStats } =
    useManageMonsterStatSectionVisibility();

  const { showFullMap, closeMap } = useToggleFullMapVisibility();

  const { showMarket, closeMarket } = useManageMarketVisibility();

  const { showPlayerKingdoms, closePlayerKingdoms } =
    useManagePlayerKingdomManagementVisibility();

  const handleShowMonsterStats = (monsterId: number) => {
    setMonsterIdToView(monsterId);

    if (monsterId === 0) {
      closeMonsterStats();

      return;
    }

    setScreenIntent(Screens.MONSTER_DETAILS, {
      monster_id: monsterId,
      toggle_monster_stat_visibility: handleShowMonsterStats,
    });

    showMonsterStats();
  };

  const renderTopLevelScreens = () => {
    if (showPlayerKingdoms) {
      return <PlayerKingdoms close_shop={closePlayerKingdoms} />;
    }

    if (showMarket) {
      return <Market close_shop={closeMarket} />;
    }

    if (showMonsterStatsSection) {
      return (
        <MonsterStatSection
          monster_id={monsterIdToView}
          toggle_monster_stat_visibility={handleShowMonsterStats}
        />
      );
    }

    if (showFullMap) {
      return <FullMap close_map={closeMap} />;
    }

    return null;
  };

  const renderBase = () => {
    return (
      <div className="relative z-10">
        <Actions showMonsterStats={handleShowMonsterStats} />
      </div>
    );
  };

  const renderContent = () => {
    const top = renderTopLevelScreens();

    if (top) {
      return top;
    }

    return renderBase();
  };

  return renderContent();
};
