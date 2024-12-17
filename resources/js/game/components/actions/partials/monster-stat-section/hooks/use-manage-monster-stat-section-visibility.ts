import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageMonsterStatSectionVisibilityDefinition from './definitions/use-manage-monster-stat-section-visibility-definition';
import { MonsterStatsEvents } from '../event-types/monster-stats';
import UseManageMonsterStatSectionVisibilityState from './types/use-manage-monster-stat-section-visibility-state';

export const useManageMonsterStatSectionVisibility =
  (): UseManageMonsterStatSectionVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const closeStatsSectionEmitter = eventSystem.isEventRegistered(
      MonsterStatsEvents.CLOSE_MONSTER_STATS
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          MonsterStatsEvents.CLOSE_MONSTER_STATS
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          MonsterStatsEvents.CLOSE_MONSTER_STATS
        );

    const [showMonsterStatsSection, setShowMonsterStatsSection] =
      useState<
        UseManageMonsterStatSectionVisibilityState['showMonsterStatsSection']
      >(false);

    useEffect(() => {
      const closeCardListener = () => setShowMonsterStatsSection(false);

      closeStatsSectionEmitter.on(
        MonsterStatsEvents.CLOSE_MONSTER_STATS,
        closeCardListener
      );

      return () => {
        closeStatsSectionEmitter.off(
          MonsterStatsEvents.CLOSE_MONSTER_STATS,
          closeCardListener
        );
      };
    }, [closeStatsSectionEmitter]);

    const showMonsterStats = () => {
      const closeStatsSectionEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(MonsterStatsEvents.CLOSE_MONSTER_STATS);

      closeStatsSectionEvent.emit(MonsterStatsEvents.CLOSE_MONSTER_STATS, true);

      setShowMonsterStatsSection(true);
    };

    return { showMonsterStatsSection, showMonsterStats };
  };
