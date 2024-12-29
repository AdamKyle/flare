import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageMonsterStatSectionVisibilityDefinition from './definitions/use-manage-monster-stat-section-visibility-definition';
import { MonsterStatsEvents } from '../event-types/monster-stats';
import UseManageMonsterStatSectionVisibilityState from './types/use-manage-monster-stat-section-visibility-state';

export const useManageMonsterStatSectionVisibility =
  (): UseManageMonsterStatSectionVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const manageStatSectionEmitter = eventSystem.isEventRegistered(
      MonsterStatsEvents.OPEN_MONSTER_STATS
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          MonsterStatsEvents.OPEN_MONSTER_STATS
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          MonsterStatsEvents.OPEN_MONSTER_STATS
        );

    const [showMonsterStatsSection, setShowMonsterStatsSection] =
      useState<
        UseManageMonsterStatSectionVisibilityState['showMonsterStatsSection']
      >(false);

    useEffect(() => {
      const closeCardListener = (visible: boolean) =>
        setShowMonsterStatsSection(visible);

      manageStatSectionEmitter.on(
        MonsterStatsEvents.OPEN_MONSTER_STATS,
        closeCardListener
      );

      return () => {
        manageStatSectionEmitter.off(
          MonsterStatsEvents.OPEN_MONSTER_STATS,
          closeCardListener
        );
      };
    }, [manageStatSectionEmitter]);

    const showMonsterStats = () => {
      const closeStatsSectionEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(MonsterStatsEvents.OPEN_MONSTER_STATS);

      closeStatsSectionEvent.emit(MonsterStatsEvents.OPEN_MONSTER_STATS, true);
    };

    const closeMonsterStats = () => {
      const closeStatsSectionEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(MonsterStatsEvents.OPEN_MONSTER_STATS);

      closeStatsSectionEvent.emit(MonsterStatsEvents.OPEN_MONSTER_STATS, false);
    };

    return { showMonsterStatsSection, closeMonsterStats, showMonsterStats };
  };
